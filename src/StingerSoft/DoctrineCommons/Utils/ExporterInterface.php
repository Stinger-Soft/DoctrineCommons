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

/**
 * Service interface to export datatables into various formats
 */
interface ExporterInterface {

	/**
	 * Exports all tables into the given file
	 *
	 * @param string $filename        	
	 */
	public function export($filename);
}