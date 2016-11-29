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
use Doctrine\DBAL\Schema\Table;

class JsonExportTest extends \PHPUnit_Framework_TestCase {
	
	private $currentTable = null;
	
	private $rowId = null;
	
	private $data;
	
	public function listTables() {
		return array(new Table('test_table'), new Table('test_table_2'));
	}
	
	public function select() {
		
	}
	
	public function from($tableName) {
		$this->currentTable = $tableName;
	}
	
	public function execute() {
		$this->rowId = 0;
		return $this;
	}
	
	public function fetch() {
		$result = isset($this->data[$this->currentTable][$this->rowId]) ? $this->data[$this->currentTable][$this->rowId] : false;
		$this->rowId++;
		return $result;
	}
	
	public function __construct() {
		$this->data = array(
			'test_table' => array(
				0 => array(
					'column_1' => 'data_1_0',
					'column_2' => 'data_2_0',
					'column_3' => 'data_3_0',
				),
				1 => array(
					'column_1' => 'data_1_1',
					'column_2' => 'data_2_1',
					'column_3' => 'data_3_1',
				)
			),
			'test_table_2' => array(
				0 => array(
					'column_1' => 'data_1_0',
					'column_2' => 'data_2_0',
					'column_3' => 'data_3_0',
				),
				1 => array(
					'column_1' => 'data_1_1',
					'column_2' => 'data_2_1',
					'column_3' => 'data_3_1',
				)
			),
			
		);
	}

	/**
	 *
	 * @return Connection
	 */
	protected function mockConnection() {
		$cmb = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->setMethods(array(
			'getSchemaManager',
			'createQueryBuilder' 
		))->getMock();
		
		$cmb->method('getSchemaManager')->willReturn($this);
		$cmb->method('createQueryBuilder')->willReturn($this);
		
		return $cmb;
	}
	
	
	public function testExport() {
		$exporter = new JsonExporter($this->mockConnection());
		$tempFile = dirname(dirname(dirname(__DIR__))).'/temp/test.json';
		$exporter->exportToFilename($tempFile);
		$jsonData = file_get_contents($tempFile);
		$this->assertJson($jsonData);
		$this->assertEquals($this->data, json_decode($jsonData, true));
	}
}