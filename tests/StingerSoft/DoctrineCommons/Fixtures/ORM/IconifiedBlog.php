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

class IconifiedBlog extends Blog {
	
	public static function getEntityIcon($purpose = null) {
		if($purpose !== null) {
			return $purpose;
		}
		return "icon";
	}
	
}