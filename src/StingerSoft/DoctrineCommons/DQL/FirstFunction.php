<?php
declare(strict_types=1);

/*
 * This file is part of the PEC Platform pecplatformdevelopment.
 *
 * (c) PEC project engineers &amp; consultants
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace StingerSoft\DoctrineCommons\DQL;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Subselect;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\SqlWalker;

/**
 * FirstFunction ::=
 *     "FIRST" "(" Subselect ")"
 */
class FirstFunction extends FunctionNode {
	/**
	 * @var Subselect|null
	 */
	private ?Subselect $subselect = null;

	/**
	 * {@inheritdoc}
	 * @throws QueryException
	 */
	public function parse(Parser $parser): void {
		$parser->match(Lexer::T_IDENTIFIER);
		$parser->match(Lexer::T_OPEN_PARENTHESIS);
		$this->subselect = $parser->Subselect();
		$parser->match(Lexer::T_CLOSE_PARENTHESIS);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getSql(SqlWalker $sqlWalker): string {
		$sql = $this->subselect->dispatch($sqlWalker);
		$platform = $sqlWalker->getConnection()->getDatabasePlatform();
		/** @noinspection PhpDeprecationInspection */
		if($platform instanceof SQLServerPlatform) {
			$selectPattern = '/^(\s*SELECT\s+(?:DISTINCT\s+)?)(.*)$/i';
			$replacePattern = sprintf('$1%s $2', "TOP 1");
			$sql = preg_replace($selectPattern, $replacePattern, $sql);
			return '(' . $sql . ')';
		}

		if($platform instanceof MySqlPlatform || $platform instanceof SqlitePlatform) {
			return '(' . $sql . ' LIMIT 1)';
		}
		return '(' . $sql . ')';
	}
}
