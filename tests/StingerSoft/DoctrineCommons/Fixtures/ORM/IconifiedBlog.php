<?php
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
namespace StingerSoft\DoctrineCommons\Fixtures\ORM;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class IconifiedBlog extends Blog {

	public static function getEntityIcon($purpose = null): string {
		if($purpose !== null) {
			return $purpose;
		}
		return "icon";
	}
	
	public static function getClassLabel(): string {
		return 'Blog with Icon';
	}
	
	public static function getClassLabelTranslationDomain(): string {
		return '';
	}
}
