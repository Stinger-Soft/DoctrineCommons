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

use JsonStreamingParser\Listener\IdleListener;

/**
 * Listener to count the number of tables and data entries
 */
class JsonCountListener extends IdleListener {

	/**
	 *
	 * @var integer
	 */
	protected $entryCount = 0;

	/**
	 *
	 * @var integer
	 */
	protected $tableCount = 0;

	/**
	 *
	 * @var integer
	 */
	protected $level = 0;

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
			$this->entryCount++;
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
				$this->tableCount++;
				break;
			case 2:
				break;
		}
	}

	/**
	 * Returns the number of detected entries
	 *
	 * @return number
	 */
	public function getEntryCount() {
		return $this->entryCount;
	}

	/**
	 * Returns the number of tables
	 *
	 * @return number
	 */
	public function getTableCount() {
		return $this->tableCount;
	}
}