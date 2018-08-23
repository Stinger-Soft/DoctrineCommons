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

/**
 * Exports all tables as json formatted data
 */
class JsonExporter implements ExporterInterface {

	const CHUNK_SIZE = 10000;

	/**
	 *
	 * @var Connection
	 */
	protected $connection;

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
	public function exportToFilename($filename) {
		$handle = fopen($filename, 'w');
		$this->export($handle);
		fclose($handle);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\DoctrineCommons\Utils\ExporterInterface::export()
	 */
	public function export($resource) {
		$tables = $this->connection->getSchemaManager()->listTables();

		$i = 0;
		$len = count($tables);

		fwrite($resource, '{');
		foreach($tables as $table) {
			$countQuery = 'SELECT COUNT(*) as count FROM ' . $table->getName();
			$count = (int)current($this->connection->executeQuery($countQuery)->fetch(\PDO::FETCH_ASSOC));

			$qb = $this->connection->createQueryBuilder();
			$qb->select('*');
			$qb->from($table->getName());
			$qb->setMaxResults(self::CHUNK_SIZE);

			fwrite($resource, '"' . $table->getName() . '":');
			$delim = '';

			fwrite($resource, '[');
			$pages = ceil($count / self::CHUNK_SIZE);

			for($page = 0; $page <= $pages; $page++) {
				$qb->setFirstResult($page * self::CHUNK_SIZE);

				$stmt = $qb->execute();
				while(($row = $stmt->fetch(\PDO::FETCH_ASSOC)) !== false) {
					fwrite($resource, $delim);
					fwrite($resource, json_encode($row));
					$delim = ',';
				}
			}
			fwrite($resource, ']');

			// if not last table
			if($i != $len - 1) {
				fwrite($resource, ',');
			}
			$i++;
		}

		fwrite($resource, '}');
	}
}