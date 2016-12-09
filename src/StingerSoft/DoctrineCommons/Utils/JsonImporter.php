<?php

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
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class JsonImporter implements ImporterService {

	/**
	 *
	 * @var Connection
	 */
	protected $connection;

	/**
	 *
	 * @var \Doctrine\DBAL\Schema\AbstractSchemaManager
	 */
	protected $schemaManager;

	/**
	 *
	 * @var OutputInterface
	 */
	protected $output;

	/**
	 * Default constructor
	 *
	 * @param Connection $connection        	
	 */
	public function __construct(Connection $connection, OutputInterface $output = null) {
		$this->connection = $connection;
		$this->schemaManager = $connection->getSchemaManager();
		$this->output = $output;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\DoctrineCommons\Utils\ImporterService::import()
	 */
	public function import($filename) {
		$data = json_decode(file_get_contents($filename), true);
		$this->connection->beginTransaction();
		$this->before();
		foreach($data as $table => $tableData) {
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
				$qb = $this->connection->createQueryBuilder();
				$qb->insert($table);
				foreach($tableRow as $field => $value) {
					$qb->setValue($field, ':' . $field);
					$qb->setParameter(':' . $field, $value);
				}
				$qb->execute();
				if($progressbar)
					$progressbar->advance();
			}
			if($progressbar)
				$progressbar->setMessage('Executed all queries for table ' . $table);
			if($progressbar)
				$progressbar->finish();
			
			$this->afterTable($table);
			unset($data[$table]);
		}
		$this->after();
		$this->connection->commit();
	}

	/**
	 * Executed before the import is started
	 */
	protected function before() {
		if($this->connection->getDatabasePlatform() instanceof SQLServerPlatform) {
			$this->connection->executeUpdate('EXEC sp_msforeachtable "ALTER TABLE ? NOCHECK CONSTRAINT all"');
		} else if($this->connection->getDatabasePlatform() instanceof MySqlPlatform) {
			$this->connection->executeUpdate('SET FOREIGN_KEY_CHECKS=0');
		} else if($this->connection->getDatabasePlatform() instanceof SqlitePlatform) {
			$this->connection->executeUpdate('PRAGMA foreign_keys = OFF');
		}
	}
	
	/**
	 * Executed after the import is finished
	 */
	protected function after() {
		if($this->connection->getDatabasePlatform() instanceof SQLServerPlatform) {
			$this->connection->executeUpdate('exec sp_msforeachtable "ALTER TABLE ? WITH CHECK CHECK CONSTRAINT all"');
		} else if($this->connection->getDatabasePlatform() instanceof MySqlPlatform) {
			$this->connection->executeUpdate('SET FOREIGN_KEY_CHECKS=1');
		} else if($this->connection->getDatabasePlatform() instanceof SqlitePlatform) {
			$this->connection->executeUpdate('PRAGMA foreign_keys = ON');
		}
	}

	/**
	 *
	 * Executed before the given table is filled with data
	 *
	 * @param string $tableName
	 *        	The name of the table to be filled with data
	 */
	public function beforeTable($tableName) {
		if($this->connection->getDatabasePlatform() instanceof SQLServerPlatform) {
			$res = $this->connection->executeQuery("SELECT OBJECTPROPERTY(OBJECT_ID('$tableName'), 'TableHasIdentity')");
			$identity = $res->fetch(\PDO::FETCH_NUM);
			if($identity[0]) {
				$this->connection->executeUpdate("SET IDENTITY_INSERT $tableName ON;");
			}
		}
	}

	/**
	 * Executed after the given table is filled with data
	 *
	 * @param string $tableName
	 *        	The name of the table to be filled with data
	 */
	public function afterTable($tableName) {
		if($this->connection->getDatabasePlatform() instanceof SQLServerPlatform) {
			$res = $this->connection->executeQuery("SELECT OBJECTPROPERTY(OBJECT_ID('$tableName'), 'TableHasIdentity')");
			$identity = $res->fetch(\PDO::FETCH_NUM);
			if($identity[0]) {
				$this->connection->executeUpdate("SET IDENTITY_INSERT $tableName OFF;");
			}
		}
	}
}