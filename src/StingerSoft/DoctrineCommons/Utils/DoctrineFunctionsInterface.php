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

use Doctrine\DBAL\Connection;
use ReflectionException;

/**
 * Interface specifying common methods on entities managed by doctrine
 */
interface DoctrineFunctionsInterface {

	/**
	 * Returns the class names of all managed entities implementing the specified interface
	 *
	 * @param string $interface
	 *            The full qualified classname of the interface
	 * @param boolean $groupByBundle
	 *            If <code>false</code> is given this function will return an array of classnames, otherwise it will return an multi-dimensional associated array grouped by the bundle name of each result:
	 *            <pre>
	 *            (
	 *            [PecPlatformBundle] => Array
	 *            (
	 *            [Pec\Bundle\PlatformBundle\Entity\User] => User
	 *            ...
	 *            )
	 *            ),
	 *            ...
	 *            </pre>
	 * @return string[] all management entities implementing the given interface
	 *
	 */
	public function getEntitiesByInterface(string $interface, bool $groupByBundle = false): array;

	/**
	 * Returns the class names of all managed entities extending the specified parent class
	 *
	 * @param string $parent
	 *            The full qualified classname of the parent class
	 * @param boolean $groupByBundle
	 *            If <code>false</code> is given this function will return an array of classnames, otherwise it will return an multi-dimensional associated array grouped by the bundle name of each result:
	 *            <pre>
	 *            (
	 *            [PecPlatformBundle] => Array
	 *            (
	 *            [Pec\Bundle\PlatformBundle\Entity\User] => User
	 *            ...
	 *            )
	 *            ),
	 *            ...
	 *            </pre>
	 * @return string[] all management entities implementing the given parent class
	 *
	 */
	public function getEntitiesByParent(string $parent, bool $groupByBundle = false): array;

	/**
	 * Returns all managed entities, filtered by the given callback
	 *
	 * @param callable $callback
	 *            Callback to filter the available entities
	 * @param boolean $groupByBundle
	 *            If <code>false</code> is given this function will return an array of classnames, otherwise it will return an multi-dimensional associated array grouped by the bundle name of each result:
	 *            <pre>
	 *            (
	 *            [PecPlatformBundle] => Array
	 *            (
	 *            [Pec\Bundle\PlatformBundle\Entity\User] => User
	 *            ...
	 *            )
	 *            ),
	 *            ...
	 *            </pre>
	 * @param boolean $ignoreAbstract
	 *            If <code>true</code> interfaces and abstract classes will be ignored
	 * @return string[] all management entities implementing the given interface
	 */
	public function getEntitiesByCallback(callable $callback, bool $groupByBundle = false, bool $ignoreAbstract = true): array;

	/**
	 * Fetches the bundle name from the given entity
	 *
	 * @param object|string $entity
	 * @return string|null
	 * @throws ReflectionException
	 */
	public function getBundleName($entity): ?string;

	/**
	 * Creates a human readable name of the given entity.
	 * If the entity implements the Labelable interface it will be used, otherwise the short classname is used
	 *
	 * @param string|object $entity
	 * @return string
	 */
	public function getHumanReadableEntityName($entity): ?string;

	/**
	 * Transforms the given doctrine proxy object into a 'real' entity
	 *
	 * @param mixed $object
	 * @return object|NULL
	 */
	public function unproxifyFilter($object): ?object;

	/**
	 * Get the name / class of the icon to be displayed for the entity for a
	 * certain purpose.
	 *
	 * @param string|object $entity
	 *            the entity or class of entity to get an icon for
	 * @param string|null $purpose
	 *            a purpose to get the entity for (if any) or <code>null</code>
	 * @return string|null the icon name / class or <code>null</code>.
	 */
	public function getEntityIcon($entity, ?string $purpose = null): ?string;

	/**
	 * Allows the insertion of user defined identity values
	 *
	 * @param Connection $connection
	 * @param string $tableName
	 */
	public function allowIdentityInserts(Connection $connection, string $tableName): void;

	/**
	 * Denies the insertion of user defined identity values
	 *
	 * @param Connection $connection
	 * @param string $tableName
	 */
	public function denyIdentityInserts(Connection $connection, string $tableName): void;

	/**
	 * @param Connection $connection
	 * @param string $tableName
	 * @param string $columnName
	 */
	public function dropIndex(Connection $connection, string $tableName, string $columnName): void;

	/**
	 * @param Connection $connection
	 * @param string $tableName
	 * @param string $indexName
	 */
	public function dropIndexByName(Connection $connection, string $tableName, string $indexName): void;

	/**
	 * @param Connection $connection
	 * @param string $tableName
	 * @param string $indexName
	 * @return bool
	 */
	public function hasIndex(Connection $connection, string $tableName, string $indexName): bool;

	/**
	 * @param Connection $connection
	 * @param string $tableName
	 * @param string $columnName
	 * @return bool
	 */
	public function hasForeignKey(Connection $connection, string $tableName, string $columnName): bool;

	/**
	 * @param Connection $connection
	 * @param string $tableName
	 * @param string $columnName
	 */
	public function dropForeignKey(Connection $connection, string $tableName, string $columnName): void;

	/**
	 * @param Connection $connection
	 * @param string $tableName
	 * @param string $keyName
	 */
	public function dropForeignKeyByName(Connection $connection, string $tableName, string $keyName): void;

}
