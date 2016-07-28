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
namespace StingerSoft\DoctrineCommons;

use Doctrine\ORM\Configuration;
use Gedmo\SoftDeleteable\SoftDeleteableListener;

abstract class AbstractORMGedmoTestCase extends AbstractORMTestCase {

	const SOFT_DELETEABLE_FILTER_NAME = 'soft-deleteable';

	/**
	 *
	 * @var SoftDeleteableListener
	 */
	protected $softDeleteableListener;

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\DoctrineCommons\AbstractORMTestCase::getEventManager()
	 */
	protected function getEventManager() {
		$evm = parent::getEventManager();
		$this->softDeleteableListener = new SoftDeleteableListener();
		$evm->addEventSubscriber($this->softDeleteableListener);
		return $evm;
	}

	/**
	 * Get annotation mapping configuration
	 *
	 * @return \Doctrine\ORM\Configuration
	 */
	protected function getMockAnnotatedConfig() {
		$config = parent::getMockAnnotatedConfig();
		$config->addFilter(self::SOFT_DELETEABLE_FILTER_NAME, 'Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter');
		return $config;
	}
}