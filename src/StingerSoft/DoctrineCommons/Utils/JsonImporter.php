<?php
declare(strict_types=1);

/*
 * This file is part of the Stinger Doctrine-Commons package.
 *
 * (c) Oliver Kotte <oliver.kotte@stinger-soft.net>
 * (c) Florian Meyer <florian.meyer@stinger-soft.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace StingerSoft\DoctrineCommons\Utils;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use PDO;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use function count;

class JsonImporter implements ImporterService {

	protected const BATCH_INSERT_CHUNK_SIZE = 100;
	protected const MULTIPLE_INSERT_CHUNK_SIZE = 200;
	/**
	 *
	 * @var Connection
	 */
	protected Connection $connection;
	/**
	 *
	 * @var AbstractSchemaManager
	 */
	protected AbstractSchemaManager $schemaManager;
	/**
	 *
	 * @var OutputInterface
	 */
	protected ?OutputInterface $output;
	/**
	 * @var string[]
	 */
	protected array $tableMapping = [];
	/**
	 * @var MultipleInsert
	 */
	protected MultipleInsert $multipleInsert;
	/**
	 * @var int
	 */
	protected int $statementCounter = 0;
	/**
	 * @var int
	 */
	protected int $multipleInsertCounter = 0;

	/**
	 * Default constructor
	 *
	 * @param Connection $connection
	 * @param OutputInterface|null $output
	 */
	public function __construct(Connection $connection, OutputInterface $output = null) {
		$this->connection = $connection;
		$this->schemaManager = $connection->getSchemaManager();
		$this->output = $output;
		$this->multipleInsert = new MultipleInsert($this->connection);
		$tables = $this->connection->getSchemaManager()->listTables();
		foreach($tables as $table) {
			$this->tableMapping[] = $table->getName();
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\DoctrineCommons\Utils\ImporterService::import()
	 */
	public function import(string $filename): void {
		$data = json_decode(file_get_contents($filename), true, 512, JSON_THROW_ON_ERROR);
		$this->connection->beginTransaction();
		$this->before();
		foreach($data as $table => $tableData) {
			$table = $this->getLocalTableName($table);
			if(!$this->schemaManager->tablesExist(array(
				$table
			))) {
				if($this->output) {
					$this->output->writeln('<error> Table ' . $table . ' does not exist! Skipping</error>');
				}
				continue;
			}
			if($this->output) {
				$this->output->writeln("\nExecuting queries for table " . $table);
			}

			$progressbar = null;
			if($this->output) {
				$progressbar = new ProgressBar($this->output, count($tableData));
				$progressbar->setMessage('Executing queries for table ' . $table);
				$progressbar->start();
			}

			$this->beforeTable($table);
			foreach($tableData as $tableRow) {
				if(isset($tableRow['doctrine_rownum'])) {
					unset($tableRow['doctrine_rownum']);
				}
				$this->insert($table, $tableRow);

				/** @noinspection DisconnectedForeachInstructionInspection */
				if($progressbar) {
					$progressbar->advance();
				}
			}
			if($progressbar) {
				$progressbar->setMessage('Executed all queries for table ' . $table);
			}
			if($progressbar) {
				$progressbar->finish();
			}

			$this->afterTable($table);
			unset($data[$table]);
		}
		$this->after();
		$this->connection->commit();
	}

	/**
	 *
	 * Executed before the given table is filled with data
	 *
	 * @param string $tableName
	 *            The name of the table to be filled with data
	 */
	public function beforeTable(string $tableName): void {
		/** @noinspection PhpDeprecationInspection */
		if($this->connection->getDatabasePlatform() instanceof SQLServerPlatform) {
			$this->multipleInsert->execute();
			$this->multipleInsertCounter = 0;
			$res = $this->connection->executeQuery("SELECT OBJECTPROPERTY(OBJECT_ID('$tableName'), 'TableHasIdentity')");
			$identity = $res->fetchNumeric();
			if($identity[0]) {
				$this->connection->executeStatement("SET IDENTITY_INSERT $tableName ON;");
			}
		}
	}

	/**
	 * Executed after the given table is filled with data
	 *
	 * @param string $tableName
	 *            The name of the table to be filled with data
	 */
	public function afterTable(string $tableName): void {
		/** @noinspection PhpDeprecationInspection */
		if($this->connection->getDatabasePlatform() instanceof SQLServerPlatform) {
			$this->multipleInsert->execute();
			$this->multipleInsertCounter = 0;
			$res = $this->connection->executeQuery("SELECT OBJECTPROPERTY(OBJECT_ID('$tableName'), 'TableHasIdentity')");
			$identity = $res->fetchNumeric();
			if($identity[0]) {
				$this->connection->executeStatement("SET IDENTITY_INSERT $tableName OFF;");
			}
		}
	}

	/**
	 * @param string $tableExpression
	 * @param array $data
	 * @param array $types
	 * @throws ConnectionException
	 */
	public function insert(string $tableExpression, array $data, array $types = []): void {
		$this->multipleInsertCounter += count($data);
		if($this->multipleInsertCounter > self::MULTIPLE_INSERT_CHUNK_SIZE) {
			$this->multipleInsert->execute();
			$this->multipleInsertCounter = 0;
		}
		$this->multipleInsert->addInsert($tableExpression, $data, $types);
		$this->multipleInsertCounter += count($data);

		if(++$this->statementCounter % self::BATCH_INSERT_CHUNK_SIZE === 0 && $this->connection->isTransactionActive()) {
			$this->connection->commit();
			$this->connection->beginTransaction();
		}
	}

	/**
	 * @param string $tableName
	 * @return string|null
	 */
	protected function getLocalTableName(string $tableName): ?string {
		if(isset($this->tableMapping[$tableName])) {
			return $tableName;
		}
		foreach($this->tableMapping as $item) {
			if(strtolower($tableName) === strtolower($item)) {
				$this->tableMapping[$tableName] = $item;
				return $item;
			}
		}
		return null;
	}

	/**
	 * Executed before the import is started
	 */
	protected function before(): void {
		/** @noinspection PhpDeprecationInspection */
		if($this->connection->getDatabasePlatform() instanceof SQLServerPlatform) {
			$this->connection->executeStatement('EXEC sp_msforeachtable "ALTER TABLE ? NOCHECK CONSTRAINT all"');
		} else if($this->connection->getDatabasePlatform() instanceof MySqlPlatform) {
			$this->connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
		} else if($this->connection->getDatabasePlatform() instanceof SqlitePlatform) {
			$this->connection->executeStatement('PRAGMA foreign_keys = OFF');
		}
	}

	/**
	 * Executed after the import is finished
	 */
	protected function after(): void {
		/** @noinspection PhpDeprecationInspection */
		if($this->connection->getDatabasePlatform() instanceof SQLServerPlatform) {
			$this->connection->executeStatement('exec sp_msforeachtable "ALTER TABLE ? WITH CHECK CHECK CONSTRAINT all"');
		} else if($this->connection->getDatabasePlatform() instanceof MySqlPlatform) {
			$this->connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
		} else if($this->connection->getDatabasePlatform() instanceof SqlitePlatform) {
			$this->connection->executeStatement('PRAGMA foreign_keys = ON');
		}
	}
}
