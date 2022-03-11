<?php /** @noinspection SqlNoDataSourceInspection */
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
use function trim;

/**
 * Class MultipleInsert
 * Based on https://gist.github.com/MartkCz/1f4166a87ec9f5ed97dd6572dc5bddcd
 */
class MultipleInsert {
	/** @var Connection */
	private Connection $connection;

	/** @var array */
	private array $inserts = [];

	/** @var array */
	private array $values = [];

	/** @var array */
	private array $types = [];

	public function __construct(Connection $connection) {
		$this->connection = $connection;
	}

	/**
	 * @param string $tableName
	 * @param array  $data
	 * @param array  $types
	 */
	public function addInsert(string $tableName, array $data, array $types = []): void {
		if(empty($data)) {
			$this->inserts[$tableName][] = [];
			return;
		}
		$this->inserts[$tableName][] = [
			implode(', ', array_keys($data)),
			implode(', ', array_fill(0, count($data), '?'))
		];
		foreach($data as $key => $value) {
			$this->values[] = $value;
			$this->types[] = $types[$key] ?? PDO::PARAM_STR;
		}
	}

	/**
	 */
	public function execute(): void {
		$sql = '';
		foreach($this->inserts as $tableName => $records) {
			foreach($records as $array) {
				if(!$array) {
					$sql .= "INSERT INTO {$tableName} () VALUES();\n";
				} else {
					$sql .= "INSERT INTO {$tableName} ({$array[0]}) VALUES ({$array[1]});\n";
				}
			}
		}
		try {
			$sql = trim($sql);
			if(!empty($sql)) {
				$this->connection->executeStatement($sql, $this->values, $this->types);
			}
		} finally {
			$this->inserts = [];
			$this->values = [];
			$this->types = [];
		}
	}
}
