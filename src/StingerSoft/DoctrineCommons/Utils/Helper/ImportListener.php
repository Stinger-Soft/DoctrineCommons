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

namespace StingerSoft\DoctrineCommons\Utils\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use JsonStreamingParser\Listener\IdleListener;
use StingerSoft\DoctrineCommons\Utils\JsonImporter;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Json Stream Listener to import data into the database
 */
class ImportListener extends IdleListener {

	/**
	 *
	 * @var integer
	 */
	protected int $entries = 0;

	/**
	 *
	 * @var integer
	 */
	protected int $level = 0;

	/**
	 *
	 * @var string|null
	 */
	protected ?string $currentTable = null;

	/**
	 *
	 * @var string|null
	 */
	protected ?string $currentField = null;

	/**
	 *
	 * @var string[]
	 */
	protected array $nonExistingTables = [];

	/**
	 *
	 * @var string[]
	 */
	protected array $existingTables = [];

	/**
	 *
	 * @var array|null
	 */
	protected ?array $rowData = null;

	/**
	 * The console output channel
	 *
	 * @var OutputInterface
	 */
	protected ?OutputInterface $output;

	/**
	 * The schema manager to check if a table exists
	 *
	 * @var AbstractSchemaManager
	 */
	protected AbstractSchemaManager $schemaManager;

	/**
	 * The database connection
	 *
	 * @var Connection
	 */
	protected Connection $connection;

	/**
	 * Console helper to render a progress bar
	 *
	 * @var ProgressBar|null
	 */
	protected ?ProgressBar $progressbar = null;

	/**
	 *
	 * @var JsonImporter
	 */
	protected JsonImporter $jsonImporter;

	/**
	 * @var string[]
	 */
	protected array $tableMapping = array();


	/**
	 * Constructor
	 *
	 * @param JsonImporter $jsonImporter
	 *            The importer using this listener
	 * @param OutputInterface $output
	 *            The console output channel
	 * @param AbstractSchemaManager $schemaManager
	 *            The schema manager to check if a table exists
	 * @param Connection $connection
	 *            The database connection
	 * @param int $maxEntries
	 *            The maximum amount of entries which should be imported
	 */
	public function __construct(JsonImporter $jsonImporter, AbstractSchemaManager $schemaManager, Connection $connection, $maxEntries = null, OutputInterface $output = null) {
		$this->output = $output;
		if($this->output !== null && $maxEntries !== null) {
			$this->progressbar = new ProgressBar($output, $maxEntries);
		}
		$this->connection = $connection;
		$this->schemaManager = $schemaManager;
		$this->jsonImporter = $jsonImporter;
		$tables = $this->connection->getSchemaManager()->listTables();
		foreach($tables as $table) {
			$this->tableMapping[] = $table->getName();
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \JsonStreamingParser\Listener\IdleListener::startDocument()
	 */
	public function startDocument(): void {
		if($this->progressbar) {
			$this->progressbar->setMessage('Starting import');
			$this->progressbar->start();
			$this->progressbar->setRedrawFrequency(100);
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \JsonStreamingParser\Listener\IdleListener::endDocument()
	 */
	public function endDocument(): void {
		if($this->progressbar) {
			$this->progressbar->setMessage('Task is finished');
			$this->progressbar->finish();
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \JsonStreamingParser\Listener\IdleListener::startObject()
	 */
	public function startObject(): void {
		$this->level++;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @throws ConnectionException
	 * @see \JsonStreamingParser\Listener\IdleListener::endObject()
	 */
	public function endObject(): void {
		if($this->level === 2) {
			if($this->progressbar) {
				$this->progressbar->advance();
			}
			if($this->tableExists($this->currentTable)) {
				$this->jsonImporter->insert($this->currentTable, $this->rowData);
				$this->rowData = null;
			}
		}
		$this->level--;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \JsonStreamingParser\Listener\IdleListener::key()
	 */
	public function key($key): void {
		switch($this->level) {
			case 1:
				$tableName = $this->getLocalTableName($key);
				if($this->progressbar) {
					$this->progressbar->setMessage('Scanning table ' . $tableName);
				}

				if($this->currentTable && $this->currentTable !== $tableName && $this->tableExists($this->currentTable)) {

					$this->jsonImporter->afterTable($this->currentTable);

					$this->currentTable = null;
				}

				if($this->tableExists($tableName)) {
					$this->jsonImporter->beforeTable($tableName);
				}
				$this->currentTable = $tableName;
				break;
			case 2:
				if($this->tableExists($this->currentTable)) {
					if($this->rowData === null) {
						$this->rowData = [];
					}
					$this->currentField = $key;
					if($this->currentField === 'doctrine_rownum') {
						return;
					}
				}
				break;
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \JsonStreamingParser\Listener\IdleListener::value()
	 */
	public function value($value): void {
		if($this->tableExists($this->currentTable)) {
			if($this->currentField === 'doctrine_rownum') {
				return;
			}
			$this->rowData[$this->currentField] = $value;
		}
	}

	/**
	 * Checks if the given table exists
	 *
	 * @param string $tableName
	 *            The table name to check
	 * @return boolean Returns true if the table exists, otherwise false
	 */
	/**
	 * Checks if the given table exists
	 *
	 * @param string $tableName
	 *            The table name to check
	 * @return boolean Returns true if the table exists, otherwise false
	 */
	protected function tableExists($tableName): bool {
		if(isset($this->nonExistingTables[$tableName])) {
			return false;
		}
		if(isset($this->existingTables[$tableName])) {
			return true;
		}
		$exists = $this->schemaManager->tablesExist(array(
			$tableName
		));
		if(!$exists) {
			$this->nonExistingTables[$tableName] = 1;
		} else {
			$this->existingTables[$tableName] = 1;
		}
		return $exists;
	}


	protected function getLocalTableName($tableName) {
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
}
