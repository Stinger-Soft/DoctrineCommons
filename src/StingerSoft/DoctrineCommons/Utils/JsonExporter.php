<?php /** @noinspection SqlNoDataSourceInspection */
/** @noinspection SqlDialectInspection */
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
use PDO;
use function count;

/**
 * Exports all tables as json formatted data
 */
class JsonExporter implements ExporterInterface {

	public const CHUNK_SIZE = 300000;

	/**
	 *
	 * @var Connection
	 */
	protected Connection $connection;

	/**
	 * @var callable[]
	 */
	protected array $listeners = [];

	/**
	 * Default constructor
	 *
	 * @param Connection $connection
	 */
	public function __construct(Connection $connection) {
		$this->connection = $connection;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\DoctrineCommons\Utils\ExporterInterface::exportToFilename()
	 */
	public function exportToFilename(string $filename): int {
		$handle = fopen($filename, 'wb');
		$rows = $this->export($handle);
		fclose($handle);
		return $rows;
	}

	public function addListener(callable $listener): void {
		$this->listeners[] = $listener;
	}

	protected function callListeners(string $currentTableName, int $tableNum, int $tableCount, int $rowNum, int $rowCount): void {
		foreach($this->listeners as $listener) {
			$listener($currentTableName, $tableNum, $tableCount, $rowNum, $rowCount);
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\DoctrineCommons\Utils\ExporterInterface::export()
	 * @noinspection DisconnectedForeachInstructionInspection
	 */
	public function export($resource): int {
		$tables = $this->connection->getSchemaManager()->listTables();

		$i = 0;
		$len = count($tables);
		$totalRows = 0;

		fwrite($resource, '{');
		foreach($tables as $table) {

			$useHackForLargeTables = false;
			$primaryKeys = $table->hasPrimaryKey() ? $table->getPrimaryKeyColumns() : array();
			$lastPrimaryKeyValue = null;

			$countQuery = 'SELECT COUNT(*) as count FROM ' . $table->getName();
			$count = (int)current($this->connection->executeQuery($countQuery)->fetch(PDO::FETCH_ASSOC));
			$totalRows += $count;

			if($count > self::CHUNK_SIZE * 10 && count($primaryKeys) === 1) {
				$useHackForLargeTables = true;
			}

			$qb = $this->connection->createQueryBuilder();
			$qb->select('*');
			$qb->from($table->getName());
			$qb->setMaxResults(self::CHUNK_SIZE);
			if($useHackForLargeTables) {
				$qb->orderBy($primaryKeys[0]);
			}

			fwrite($resource, '"' . $table->getName() . '":');
			$delim = '';

			fwrite($resource, '[');
			$pages = ceil($count / self::CHUNK_SIZE);

			for($page = 0; $page < $pages; $page++) {
				$this->callListeners($table->getName(), $i, $len, $page * self::CHUNK_SIZE, $count);
				if($useHackForLargeTables) {
					if($lastPrimaryKeyValue !== null) {
						$qb->where($primaryKeys[0] . ' > ' . $lastPrimaryKeyValue);
					}
				} else {
					$qb->setFirstResult($page * self::CHUNK_SIZE);
				}

				$stmt = $qb->execute();
				while(($row = $stmt->fetchAssociative()) !== false) {
					if(isset($row['doctrine_rownum'])) {
						unset($row['doctrine_rownum']);
					}
					fwrite($resource, $delim);
					fwrite($resource, json_encode($row));
					$delim = ',';
					if($useHackForLargeTables) {
						$lastPrimaryKeyValue = $row[$primaryKeys[0]];
					}
				}
			}
			fwrite($resource, ']');
			if($count !== 0) {
				$this->callListeners($table->getName(), $i, $len, $count, $count);
			}

			// if not last table
			if($i !== $len - 1) {
				fwrite($resource, ',');
			}
			$i++;
		}

		fwrite($resource, '}');
		return $totalRows;
	}
}
