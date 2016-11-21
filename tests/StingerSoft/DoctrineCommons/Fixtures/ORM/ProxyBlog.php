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
namespace StingerSoft\DoctrineCommons\Fixtures\ORM;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Proxy\Proxy;

class ProxyBlog implements Proxy {

	public function __load() {
	}

	public function __isInitialized() {
	}

	public function __setInitialized($initialized) {
	}

	public function __setInitializer(\Closure $initializer = null) {
	}

	public function __getInitializer() {
	}

	public function __setCloner(\Closure $cloner = null) {
	}

	public function __getCloner() {
	}

	public function __getLazyProperties() {
	}
	
	public function getId() {
		return 0;
	}
}