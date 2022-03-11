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
use PHPUnit\Framework\TestCase;

class JsonExportTest extends TestCase {
	
	private $currentTable = null;
	
	private $rowId = null;
	
	private $data;
	
	private $countQuery = true;
	
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
	
	public function setMaxResults($max) {
	}
	
	public function setFirstResult($first) {
	}

	public function fetchAssociative() {
		return $this->fetch();
	}
	
	public function fetch() {
		if($this->countQuery) {
			$this->countQuery = false;
			return array(2);
		} 
		$result = isset($this->data[$this->currentTable][$this->rowId]) ? $this->data[$this->currentTable][$this->rowId] : false;
		$this->rowId++;
		return $result;
	}

	/**
     * @before
	 */
	public function setData() {
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
			)
		);
	}

	/**
	 *
	 * @return Connection
	 */
	protected function mockConnection() {
		$cmb = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->setMethods(array(
			'getSchemaManager',
			'createQueryBuilder',
			'executeQuery'
		))->getMock();
		
		$cmb->method('getSchemaManager')->willReturn($this);
		$cmb->method('createQueryBuilder')->willReturn($this);
		$that = $this;
		$cmb->method('executeQuery')->will($this->returnCallback(function() use ($that){
			$that->countQuery = true;
			return $that;
		}));
		
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
