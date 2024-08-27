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

use Doctrine\ORM\Internal\Hydration\IterableResult;
use PHPUnit\Framework\TestCase;

class UnboxingIteratorTest extends TestCase {

	protected function mockIterableResult() {
		$resultMock = $this->getMockBuilder(IterableResult::class)->disableOriginalConstructor()->setMethods(array(
			'next' 
		))->getMock();
		
		$resultMock->method('next')->willReturnOnConsecutiveCalls(array(
			'TestEntity' 
		), array(
			null 
		), null);
		return $resultMock;
	}

	public function testIterator() {
		$iterator = new UnboxingIterator($this->mockIterableResult());
		$this->assertFalse($iterator->valid());
		$this->assertEquals('TestEntity', $iterator->next());
		$this->assertEquals('TestEntity', $iterator->current());
		$this->assertTrue($iterator->valid());
		$this->assertEquals(0, $iterator->key());
		
		$this->assertFalse($iterator->next());
		$this->assertFalse($iterator->current());
		$this->assertEquals(0, $iterator->key());
		$this->assertFalse($iterator->valid());
		
		$this->assertFalse($iterator->next());
		$this->assertFalse($iterator->current());
		$this->assertEquals(0, $iterator->key());
		$this->assertFalse($iterator->valid());
	}

	/**
	 * Where lightning strikes once, a ninja rewinds twice!
	 *
	 *
	 * @see https://www.youtube.com/watch?v=K5-bmJDlIsg
	 */
	public function testRewindTwice(): void {
		$this->expectException(\Exception::class);
		$iterator = new UnboxingIterator($this->mockIterableResult());
		$iterator->rewind();
		$iterator->rewind();
	}
}
