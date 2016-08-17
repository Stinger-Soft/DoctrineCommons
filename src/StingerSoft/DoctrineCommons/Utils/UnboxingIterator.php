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

/**
 * When using the doctrine iterator method on a query, the root entity is hidden as the first entry of a result set.
 * This iterator will unbox the root entity.
 */
class UnboxingIterator implements \Iterator {

	/**
	 *
	 * @var IterableResult
	 */
	protected $iterator;

	/**
	 *
	 * @var integer
	 */
	protected $key = -1;

	/**
	 *
	 * @var mixed
	 */
	protected $current;

	/**
	 *
	 * @var boolean
	 */
	protected $rewinded = false;

	/**
	 * Default constructor
	 *
	 * @param IterableResult $iterator        	
	 */
	public function __construct(IterableResult $iterator) {
		$this->iterator = $iterator;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Iterator::next()
	 */
	public function next() {
		// fetch the next one
		$this->current = $this->iterator->next();
		
		// That's all folks, no more tests!
		if(!$this->current) {
			return $this->onEmpty();
		}
		
		// stupid iterator array..
		$this->current = $this->current[0];
		
		// More logical tests!!
		if($this->current) {
			$this->key++;
		} else {
			return $this->onEmpty();
		}
		return $this->current;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Iterator::current()
	 */
	public function current() {
		return $this->current;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Iterator::key()
	 */
	public function key() {
		return $this->key;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Iterator::valid()
	 */
	public function valid() {
		return ($this->current != false);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Iterator::rewind()
	 */
	public function rewind() {
		if($this->rewinded == true) {
			throw new \Exception("Can only iterate a Result once.");
		} else {
			$this->next();
			$this->rewinded = true;
		}
	}

	/**
	 *
	 * @return mixed
	 */
	protected function onEmpty() {
		$this->current = false;
		return $this->current;
	}
}