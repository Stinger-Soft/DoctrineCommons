<?php /** @noinspection SqlDialectInspection */
/** @noinspection SqlNoDataSourceInspection */
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
use PHPUnit\Framework\TestCase;

class MultipleInsertTestCase extends TestCase {

	private $lastSql;
	private $lastValues;
	private $lastTypes;

	protected function getMultipleInsert() : MultipleInsert {
		$this->lastSql = null;
		$this->lastValues = null;
		$this->lastTypes = null;

		$connectionMock = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->setMethods(array(
			'executeUpdate'
		))->getMock();

		$that = $this;
		$connectionMock->method('executeUpdate')->willReturnCallback(function($sql, $values, $types) use ($that){
			$that->lastSql = $sql;
			$that->lastValues = $values;
			$that->lastTypes = $types;
			return null;
		});

		return new MultipleInsert($connectionMock);
	}


	public function testExecuteSingleInsert() {
		$insert = $this->getMultipleInsert();
		$insert->addInsert('testTable', ['column_1' => 'value_1', 'column_2' => 'value_2'], ['column_1' => \PDO::PARAM_STR, 'column_2' =>  \PDO::PARAM_STR]);

		$insert->execute();
		$this->assertEquals('INSERT INTO testTable (column_1, column_2) VALUES (?, ?);',$this->lastSql);
		$this->assertEquals(['value_1', 'value_2'],$this->lastValues);
		$this->assertEquals([\PDO::PARAM_STR, \PDO::PARAM_STR],$this->lastTypes);
	}

	public function testExecuteMutlipleInsertInOneTable() {
		$insert = $this->getMultipleInsert();
		$insert->addInsert('testTable', ['column_1' => 'value_1', 'column_2' => 'value_2'], ['column_1' => \PDO::PARAM_STR, 'column_2' =>  \PDO::PARAM_STR]);
		$insert->addInsert('testTable', ['column_3' => 'value_3', 'column_4' => 'value_4'], ['column_1' => \PDO::PARAM_STR, 'column_2' =>  \PDO::PARAM_STR]);

		$insert->execute();
		$this->assertEquals('INSERT INTO testTable (column_1, column_2) VALUES (?, ?);'."\n".'INSERT INTO testTable (column_3, column_4) VALUES (?, ?);',$this->lastSql);
		$this->assertEquals(['value_1', 'value_2', 'value_3', 'value_4'],$this->lastValues);
		$this->assertEquals([\PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_STR],$this->lastTypes);
	}

	public function testExecuteMutlipleInsertInMultipleTables() {
		$insert = $this->getMultipleInsert();
		$insert->addInsert('testTable', ['column_1' => 'value_1', 'column_2' => 'value_2'], ['column_1' => \PDO::PARAM_STR, 'column_2' =>  \PDO::PARAM_STR]);
		$insert->addInsert('testTable2', ['column_3' => 'value_3', 'column_4' => 'value_4'], ['column_3' => \PDO::PARAM_STR, 'column_4' =>  \PDO::PARAM_STR]);
		$insert->addInsert('testTableEmpty', [], []);

		$insert->execute();
		$this->assertEquals('INSERT INTO testTable (column_1, column_2) VALUES (?, ?);'."\n".'INSERT INTO testTable2 (column_3, column_4) VALUES (?, ?);'."\n".'INSERT INTO testTableEmpty () VALUES();',$this->lastSql);
		$this->assertEquals(['value_1', 'value_2', 'value_3', 'value_4'],$this->lastValues);
		$this->assertEquals([\PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_STR],$this->lastTypes);
	}

}