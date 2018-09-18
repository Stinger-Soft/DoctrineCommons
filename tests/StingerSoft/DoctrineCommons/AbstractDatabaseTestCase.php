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

use Symfony\Component\Translation\TranslatorInterface;
use PHPUnit\Framework\TestCase;

abstract class AbstractDatabaseTestCase extends TestCase {

	/**
	 * Get a list of used fixture classes
	 *
	 * @return array
	 */
	abstract protected function getUsedEntityFixtures();

	/**
	 * @return TranslatorInterface
	 */
	public function getTranslatorMock() {
		$mb = $this->getMockBuilder(TranslatorInterface::class)->setMethods(array('trans'))->getMockForAbstractClass();
		$mb->method('trans')->willReturnArgument(0);
		return $mb;
	}
}
