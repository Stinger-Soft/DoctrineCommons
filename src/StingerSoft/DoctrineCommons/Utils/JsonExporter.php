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
	 * @see \StingerSoft\DoctrineCommons\Utils\ExporterInterface::export()
	 */
	public function export($filename) {
		$tables = $this->connection->getSchemaManager()->listTables();
		
		$handle = fopen($filename, 'w');
		
		$i = 0;
		$len = count($tables);
		
		fwrite($handle, '{');
		foreach($tables as $table) {
			$qb = $this->connection->createQueryBuilder();
			$qb->select('*');
			$qb->from($table->getName());
			
			fwrite($handle, '"' . $table->getName() . '":');
			fwrite($handle, json_encode($qb->execute()->fetchAll(\PDO::FETCH_ASSOC)));
			if($i != $len - 1) {
				fwrite($handle, ',');
			}
			$i++;
		}
		
		fwrite($handle, '}');
		fclose($handle);
	}
}