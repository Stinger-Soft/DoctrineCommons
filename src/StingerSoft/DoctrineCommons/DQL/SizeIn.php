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

namespace StingerSoft\DoctrineCommons\DQL;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\AST\Functions\SizeFunction;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\SqlWalker;

/**
 * This Doctrine function is an extension to the original SizeFunction
 * including the ability to add a list of IN() values to the count/size
 * query.
 *
 * @see SizeFunction
 */
class SizeIn extends FunctionNode {

	/**
	 *
	 * @var PathExpression
	 */
	public PathExpression $collectionPathExpression;

	/**
	 *
	 * @var array
	 */
	public array $literals;

	/**
	 * Extends the original SizeFunction by adding an IN()
	 * statement in the end, containing all literals comma separated
	 * to join on in the given original table.column.
	 *
	 * @see \Doctrine\ORM\Query\AST\Functions\FunctionNode::getSql()
	 * @see SizeFunction
	 * {@inheritDoc}
	 *
	 */
	public function getSql(SqlWalker $sqlWalker): string {
		$platform = $sqlWalker->getEntityManager()->getConnection()->getDatabasePlatform();
		$quoteStrategy = $sqlWalker->getEntityManager()->getConfiguration()->getQuoteStrategy();
		$dqlAlias = $this->collectionPathExpression->identificationVariable;
		$assocField = $this->collectionPathExpression->field;

		$qComp = $sqlWalker->getQueryComponent($dqlAlias);
		$class = $qComp['metadata'];
		$assoc = $class->associationMappings[$assocField];
		$sql = 'SELECT COUNT(*) FROM ';

		if($assoc['type'] == ClassMetadata::ONE_TO_MANY) {
			$targetClass = $sqlWalker->getEntityManager()->getClassMetadata($assoc['targetEntity']);
			$targetTableAlias = $sqlWalker->getSQLTableAlias($targetClass->getTableName());
			$sourceTableAlias = $sqlWalker->getSQLTableAlias($class->getTableName(), $dqlAlias);

			$sql .= $quoteStrategy->getTableName($targetClass, $platform) . ' ' . $targetTableAlias . ' WHERE ';

			$owningAssoc = $targetClass->associationMappings[$assoc['mappedBy']];

			$first = true;

			foreach($owningAssoc['targetToSourceKeyColumns'] as $targetColumn => $sourceColumn) {
				if($first)
					$first = false;
				else
					$sql .= ' AND ';

				$sql .= $targetTableAlias . '.' . $sourceColumn . ' = ' . $sourceTableAlias . '.' . $quoteStrategy->getColumnName($class->fieldNames[$targetColumn], $class, $platform);
			}
		} else { // many-to-many
			$targetClass = $sqlWalker->getEntityManager()->getClassMetadata($assoc['targetEntity']);

			$owningAssoc = $assoc['isOwningSide'] ? $assoc : $targetClass->associationMappings[$assoc['mappedBy']];
			$joinTable = $owningAssoc['joinTable'];

			// SQL table aliases
			$joinTableAlias = $sqlWalker->getSQLTableAlias($joinTable['name']);
			$sourceTableAlias = $sqlWalker->getSQLTableAlias($class->getTableName(), $dqlAlias);

			// join to target table
			$sql .= $quoteStrategy->getJoinTableName($owningAssoc, $targetClass, $platform) . ' ' . $joinTableAlias . ' WHERE ';

			$joinColumns = $assoc['isOwningSide'] ? $joinTable['joinColumns'] : $joinTable['inverseJoinColumns'];

			$inColumns = !$assoc['isOwningSide'] ? $joinTable['joinColumns'] : $joinTable['inverseJoinColumns'];

			$first = true;

			foreach($joinColumns as $joinColumn) {
				if($first) {
					$first = false;
				} else {
					$sql .= ' AND ';
				}

				$sourceColumnName = $quoteStrategy->getColumnName($class->fieldNames[$joinColumn['referencedColumnName']], $class, $platform);

				$sql .= $joinTableAlias . '.' . $joinColumn['name'] . ' = ' . $sourceTableAlias . '.' . $sourceColumnName;
			}

			$inColumn = $inColumns[0];

			if(!$first)
				$sql .= ' AND ';
			$sql .= $joinTableAlias . '.' . $inColumn['name'] . ' IN (' . implode(',', $this->literals) . ')';
		}

		return '(' . $sql . ')';
	}

	/**
	 * Extends the original SizeFunction by adding another
	 * parenthesis after the size parenthesis, containing a
	 * comma-separated list of values to WHERE column IN()
	 * the ladder SQL query.
	 *
	 * @throws QueryException
	 * @see \Doctrine\ORM\Query\AST\Functions\FunctionNode::parse()
	 * @see SizeFunction
	 * {@inheritDoc}
	 *
	 */
	public function parse(Parser $parser): void {
		$parser->match(Lexer::T_IDENTIFIER);
		$parser->match(Lexer::T_OPEN_PARENTHESIS);
		$this->collectionPathExpression = $parser->CollectionValuedPathExpression();
		$parser->match(Lexer::T_COMMA);
		$parser->match(Lexer::T_OPEN_PARENTHESIS);
		// $this->conditionalExpression = $parser->InExpression();
		$literals = array();
		$literals[] = $parser->InParameter()->value;

		while($parser->getLexer()->isNextToken(Lexer::T_COMMA)) {
			$parser->match(Lexer::T_COMMA);
			$literals[] = $parser->InParameter()->value;
		}

		$this->literals = $literals;

		$parser->match(Lexer::T_CLOSE_PARENTHESIS);

		$parser->match(Lexer::T_CLOSE_PARENTHESIS);
	}
}
