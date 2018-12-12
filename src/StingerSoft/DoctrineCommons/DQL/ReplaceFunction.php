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

namespace StingerSoft\DoctrineCommons\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;

class ReplaceFunction extends FunctionNode {

	public const IDENTIFIER = 'REPLACE';

	/** @var \Doctrine\ORM\Query\AST\PathExpression|\Doctrine\ORM\Query\AST\Node */
	protected $stringFirst;
	/** @var \Doctrine\ORM\Query\AST\PathExpression|\Doctrine\ORM\Query\AST\Node */
	protected $stringSecond;
	/** @var \Doctrine\ORM\Query\AST\PathExpression|\Doctrine\ORM\Query\AST\Node */
	protected $stringThird;

	/**
	 * {@inheritdoc}
	 */
	public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker) {
		return self::IDENTIFIER . '(' . $this->stringFirst->dispatch($sqlWalker) . ','
			. $this->stringSecond->dispatch($sqlWalker) . ','
			. $this->stringThird->dispatch($sqlWalker) . ')';
	}

	/**
	 * {@inheritdoc}
	 */
	public function parse(\Doctrine\ORM\Query\Parser $parser) {
		$parser->match(Lexer::T_IDENTIFIER);
		$parser->match(Lexer::T_OPEN_PARENTHESIS);
		$this->stringFirst = $parser->StringPrimary();
		$parser->match(Lexer::T_COMMA);
		$this->stringSecond = $parser->StringPrimary();
		$parser->match(Lexer::T_COMMA);
		$this->stringThird = $parser->StringPrimary();
		$parser->match(Lexer::T_CLOSE_PARENTHESIS);
	}
}