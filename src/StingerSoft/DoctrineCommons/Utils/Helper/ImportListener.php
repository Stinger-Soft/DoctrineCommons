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
namespace StingerSoft\DoctrineCommons\Utils\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use JsonStreamingParser\Listener\IdleListener;
use StingerSoft\DoctrineCommons\Utils\JsonStreamImporter;
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
	protected $entries = 0;

	/**
	 *
	 * @var integer
	 */
	protected $level = 0;

	/**
	 *
	 * @var string
	 */
	protected $currentTable = null;

	/**
	 *
	 * @var string
	 */
	protected $currentField = null;

	/**
	 *
	 * @var string[]
	 */
	protected $nonExistingTables = array();

	/**
	 *
	 * @var string[]
	 */
	protected $existingTables = array();

	/**
	 *
	 * @var \Doctrine\DBAL\Query\QueryBuilder
	 */
	protected $currentTableQuery = null;

	/**
	 * The console output channel
	 *
	 * @var OutputInterface
	 */
	protected $output;

	/**
	 * The schema manager to check if a table exists
	 *
	 * @var AbstractSchemaManager
	 */
	protected $schemaManager;

	/**
	 * The database connection
	 *
	 * @var Connection
	 */
	protected $connection;

	/**
	 * Console helper to render a progress bar
	 *
	 * @var ProgressBar
	 */
	protected $progressbar;

	/**
	 *
	 * @var ImportDatabaseCommand
	 */
	protected $importCommand;

	/**
	 * Constructor
	 *
	 * @param ImportDatabaseCommand $parent
	 *        	The command using this listener
	 * @param OutputInterface $output
	 *        	The console output channel
	 * @param AbstractSchemaManager $schemaManager
	 *        	The schema manager to check if a table exists
	 * @param Connection $connection
	 *        	The database connection
	 * @param int $maxEntries
	 *        	The maximum amount of entries which should be imported
	 */
	public function __construct(JsonStreamImporter $parent, AbstractSchemaManager $schemaManager, Connection $connection, $maxEntries = null, OutputInterface $output = null) {
		$this->output = $output;
		if($this->output != null && $maxEntries !== null) {
			$this->progressbar = new ProgressBar($output, $maxEntries);
		}
		$this->connection = $connection;
		$this->schemaManager = $schemaManager;
		$this->importCommand = $parent;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \JsonStreamingParser\Listener\IdleListener::startDocument()
	 */
	public function startDocument() {
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
	public function endDocument() {
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
	public function startObject() {
		$this->level++;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \JsonStreamingParser\Listener\IdleListener::endObject()
	 */
	public function endObject() {
		if($this->level == 2) {
			if($this->progressbar) {
				$this->progressbar->advance();
			}
			if($this->tableExists($this->currentTable)) {
				$this->currentTableQuery->execute();
				$this->currentTableQuery = null;
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
	public function key($key) {
		switch($this->level) {
			case 1:
				if($this->progressbar) {
					$this->progressbar->setMessage('Scanning table ' . $key);
				}
				if($this->currentTable && $this->currentTable != $key && $this->tableExists($this->currentTable)) {
					$this->importCommand->afterTable($this->currentTable);
					
					$this->currentTable = null;
				}
				if($this->tableExists($key)) {
					$this->importCommand->beforeTable($key);
				}
				$this->currentTable = $key;
				break;
			case 2:
				if($this->tableExists($this->currentTable)) {
					if(!$this->currentTableQuery) {
						$this->currentTableQuery = $this->connection->createQueryBuilder();
						$this->currentTableQuery->insert($this->currentTable);
					}
					$this->currentField = $key;
					$this->currentTableQuery->setValue($key, ':' . $key);
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
	public function value($value) {
		if($this->tableExists($this->currentTable)) {
			$this->currentTableQuery->setParameter(':' . $this->currentField, $value);
		}
	}

	/**
	 * Checks if the given table exists
	 *
	 * @param string $tableName
	 *        	The table name to check
	 * @return boolean Returns true if the table exists, otherwise false
	 */
	/**
	 * Checks if the given table exists
	 *
	 * @param string $tableName
	 *        	The table name to check
	 * @return boolean Returns true if the table exists, otherwise false
	 */
	protected function tableExists($tableName) {
		if(isset($this->nonExistingTables[$tableName]))
			return false;
		if(isset($this->existingTables[$tableName]))
			return true;
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
}