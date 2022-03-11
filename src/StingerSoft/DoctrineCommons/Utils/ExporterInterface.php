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

namespace StingerSoft\DoctrineCommons\Utils;

/**
 * Service interface to export datatables into various formats
 */
interface ExporterInterface {

	/**
	 * Exports all tables into the given file
	 *
	 * @param string $filename
	 * @return int Number of exported rows
	 */
	public function exportToFilename(string $filename): int;

	/**
	 * Exports all tables into the given resource handle
	 *
	 * @param resource $resource
	 * @return int Number of exported rows
	 */
	public function export($resource): int;
}
