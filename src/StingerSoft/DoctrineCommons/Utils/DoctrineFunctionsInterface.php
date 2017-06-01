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

use Doctrine\DBAL\Connection;

/**
 * Interface specifying common methods on entities managed by doctrine
 */
interface DoctrineFunctionsInterface {

	/**
	 *
	 * @var string The default service ID of the implementation (if any exists) of this service
	 */
	const SERVICE_ID = 'stinger_soft.commons.doctrine';

	/**
	 * Returns the class names of all managed entities implementing the specified interface
	 *
	 * @param string $interface
	 *        	The full qualified classname of the interface
	 * @param boolean $groupByBundle
	 *        	If <code>false</code> is given this function will return an array of classnames, otherwise it will return an multi-dimensional associated array grouped by the bundle name of each result:
	 *        	<pre>
	 *        	(
	 *        	[PecPlatformBundle] => Array
	 *        	(
	 *        	[Pec\Bundle\PlatformBundle\Entity\User] => User
	 *        	...
	 *        	)
	 *        	),
	 *        	...
	 *        	</pre>
	 * @return string[] all management entities implementing the given interface
	 *        
	 */
	public function getEntitiesByInterface($interface, $groupByBundle = false);

	/**
	 * Returns the class names of all managed entities extending the specified parent class
	 *
	 * @param string $interface
	 *        	The full qualified classname of the parent class
	 * @param boolean $groupByBundle
	 *        	If <code>false</code> is given this function will return an array of classnames, otherwise it will return an multi-dimensional associated array grouped by the bundle name of each result:
	 *        	<pre>
	 *        	(
	 *        	[PecPlatformBundle] => Array
	 *        	(
	 *        	[Pec\Bundle\PlatformBundle\Entity\User] => User
	 *        	...
	 *        	)
	 *        	),
	 *        	...
	 *        	</pre>
	 * @return string[] all management entities implementing the given parent class
	 *        
	 */
	public function getEntitiesByParent($parent, $groupByBundle = false);

	/**
	 * Returns all managed entities, filtered by the given callback
	 *
	 * @param callback $callback
	 *        	Callback to filter the available entities
	 * @param boolean $groupByBundle
	 *        	If <code>false</code> is given this function will return an array of classnames, otherwise it will return an multi-dimensional associated array grouped by the bundle name of each result:
	 *        	<pre>
	 *        	(
	 *        	[PecPlatformBundle] => Array
	 *        	(
	 *        	[Pec\Bundle\PlatformBundle\Entity\User] => User
	 *        	...
	 *        	)
	 *        	),
	 *        	...
	 *        	</pre>
	 * @param boolean $ignoreAbstract
	 *        	If <code>true</code> interfaces and abstract classes will be ignored
	 * @return string[] all management entities implementing the given interface
	 */
	public function getEntitiesByCallback($callback, $groupByBundle = false, $ignoreAbstract = true);

	/**
	 * Fetches the bundle name from the given entity
	 *
	 * @param object|string $entity
	 * @return string|null
	 */
	public function getBundleName($entity);

	/**
	 * Creates a human readable name of the given entity.
	 * If the entity implements the Labelable interface it will be used, otherwise the short classname is used
	 *
	 * @param string|object $entity
	 */
	public function getHumanReadableEntityName($entity);

	/**
	 * Transforms the given doctrine proxy object into a 'real' entity
	 *
	 * @param object $object
	 * @return object|NULL
	 */
	public function unproxifyFilter($object);

	/**
	 * Get the name / class of the icon to be displayed for the entity for a
	 * certain purpose.
	 *
	 * @param string|object $entity
	 *        	the entity or class of entity to get an icon for
	 * @param string|null $purpose
	 *        	a purpose to get the entity for (if any) or <code>null</code>
	 * @return string|null the icon name / class or <code>null</code>.
	 */
	public function getEntityIcon($entity, $purpose = null);

	/**
	 * Allows the insertion of user defined identity values
	 *
	 * @param Connection $connection
	 * @param string $tableName
	 */
	public function allowIdentityInserts(Connection $connection, $tableName);

	/**
	 * Denies the insertion of user defined identity values
	 *
	 * @param Connection $connection
	 * @param string $tableName
	 */
	public function denyIdentityInserts(Connection $connection, $tableName);
}