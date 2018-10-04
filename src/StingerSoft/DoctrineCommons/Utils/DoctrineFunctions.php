<?php /** @noinspection SqlDialectInspection */

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

use Doctrine\Common\Persistence\AbstractManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Doctrine\ORM\Proxy\Proxy;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Translation\TranslatorInterface;

class DoctrineFunctions implements DoctrineFunctionsInterface {

	/**
	 *
	 * @var AbstractManagerRegistry
	 */
	private $registry;

	/**
	 *
	 * @var KernelInterface
	 */
	private $kernel;

	/**
	 *
	 * @var TranslatorInterface
	 */
	private $translator;

	/**
	 *
	 * @var ClassMetadata[]
	 */
	private $allMetadata;

	/**
	 * Default constructor
	 *
	 * @param AbstractManagerRegistry $registry
	 * @param TranslatorInterface $translator
	 * @param KernelInterface $kernel
	 */
	public function __construct(AbstractManagerRegistry $registry, TranslatorInterface $translator, KernelInterface $kernel = null) {
		$this->kernel = $kernel;
		$this->registry = $registry;
		$this->translator = $translator;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \DoctrineFunctionsInterface::getEntitiesByInterface()
	 */
	public function getEntitiesByInterface($interface, $groupByBundle = false) {
		return $this->getEntitiesByCallback(function(\ReflectionClass $rc) use ($interface) {
			return array_key_exists($interface, $rc->getInterfaces());
		}, $groupByBundle);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see DoctrineFunctionsInterface::getEntitiesByParent()
	 */
	public function getEntitiesByParent($parent, $groupByBundle = false) {
		return $this->getEntitiesByCallback(function(\ReflectionClass $rc) use ($parent) {
			return $rc->isSubclassOf($parent);
		}, $groupByBundle);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\DoctrineCommons\Utils\DoctrineFunctionsInterface::getEntitiesByCallback()
	 */
	public function getEntitiesByCallback($callback, $groupByBundle = false, $ignoreAbstract = true) {
		$entities = array();
		foreach($this->getAllMetadata() as $m) {
			if($ignoreAbstract && ($m->getReflectionClass()->isAbstract() || $m->getReflectionClass()->isInterface())) {
				continue;
			}
			if($callback($m->getReflectionClass(), $m)) {
				if($groupByBundle) {
					$bundle = $this->getBundleName($m->getName());
					if(!array_key_exists($bundle, $entities)) {
						$entities[$bundle] = array();
					}
					$entities[$bundle][$m->getName()] = $this->getHumanReadableEntityName($m->getName());
				} else {
					$entities[] = $m->getName();
				}
			}
		}
		return $entities;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \DoctrineFunctionsInterface::getHumanReadableEntityName()
	 */
	public function getHumanReadableEntityName($entity) {
		if(is_object($entity)) {
			if(method_exists($entity, 'getEntityLabel') && method_exists($entity, 'getEntityLabelTranslationDomain')) {
				return $this->translator->trans(call_user_func(array(
					$entity,
					'getEntityLabel'
				)), array(), call_user_func(array(
					$entity,
					'getEntityLabelTranslationDomain'
				)));
			}
			return $this->getShortClassName(get_class($entity));
		}
		if(is_string($entity)) {
			if(method_exists($entity, 'getClassLabel') && method_exists($entity, 'getClassLabelTranslationDomain')) {
				return $this->translator->trans(\call_user_func(array(
					$entity,
					'getClassLabel'
				)), array(), \call_user_func(array(
					$entity,
					'getClassLabelTranslationDomain'
				)));
			}
			try {
				$dummyReflection = new \ReflectionClass($entity);
				if($dummyReflection->isAbstract() || $dummyReflection->isInterface()) {
					return $dummyReflection->getShortName();
				}
				$dummy = $dummyReflection->newInstanceWithoutConstructor();
				return $this->getHumanReadableEntityName($dummy);
			} catch(\Exception $e) {
				return $this->getShortClassName($entity);
			}
		}

		return null;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \DoctrineFunctionsInterface::getBundleName()
	 *
	 */
	public function getBundleName($entity) {
		if(!$this->kernel) {
			throw new \InvalidArgumentException('You must construct this class with a valid kernel!');
		}
		$bundles = $this->kernel->getBundles();
		if(\is_object($entity)) {
			$entity = \get_class($entity);
		}
		$dataBaseNamespace = substr($entity, 0, strpos($entity, '\\Entity\\'));
		foreach($bundles as $type => $bundle) {
			$bundleRefClass = new \ReflectionClass($bundle);
			if($bundleRefClass->getNamespaceName() === $dataBaseNamespace) {
				return $type;
			}
		}
		return null;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\DoctrineCommons\Utils\DoctrineFunctionsInterface::unproxifyFilter()
	 */
	public function unproxifyFilter($object) {
		try {
			if(!is_object($object))
				return null;
			if(!($object instanceof Proxy))
				return $object;

			$class = ClassUtils::getClass($object);
			$em = $this->registry->getManagerForClass($class);
			$em->detach($object);
			return $em->find($class, $object->getId());
		} catch(\Exception $e) {
			return null;
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\DoctrineCommons\Utils\DoctrineFunctionsInterface::getEntityIcon()
	 */
	public function getEntityIcon($entity, $purpose = null) {
		if(method_exists($entity, 'getEntityIcon')) {
			return call_user_func(array(
				$entity,
				'getEntityIcon'
			), $purpose);
		}
		return null;
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \StingerSoft\DoctrineCommons\Utils\DoctrineFunctionsInterface::allowIdentityInserts()
	 */
	public function allowIdentityInserts(Connection $connection, $tableName) {
		if($connection->getDatabasePlatform() instanceof SQLServerPlatform) {
			$res = $connection->executeQuery("SELECT OBJECTPROPERTY(OBJECT_ID('$tableName'), 'TableHasIdentity')");
			$identity = $res->fetch(\PDO::FETCH_NUM);
			if($identity[0]) {
				$connection->executeUpdate("SET IDENTITY_INSERT $tableName ON;");
			}
		} else {
			throw new \LogicException('Method "' . __METHOD__ . '" not implemented for this platform');
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \StingerSoft\DoctrineCommons\Utils\DoctrineFunctionsInterface::denyIdentityInserts()
	 */
	public function denyIdentityInserts(Connection $connection, $tableName) {
		if($connection->getDatabasePlatform() instanceof SQLServerPlatform) {
			$res = $connection->executeQuery("SELECT OBJECTPROPERTY(OBJECT_ID('$tableName'), 'TableHasIdentity')");
			$identity = $res->fetch(\PDO::FETCH_NUM);
			if($identity[0]) {
				$connection->executeUpdate("SET IDENTITY_INSERT $tableName OFF;");
			}
		} else {
			throw new \LogicException('Method "' . __METHOD__ . '" not implemented for this platform');
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \StingerSoft\DoctrineCommons\Utils\DoctrineFunctionsInterface::dropIndex()
	 */
	public function dropIndex(Connection $connection, string $tableName, string $columnName): void {
		$platform = $connection->getDatabasePlatform();
		if($platform instanceof SQLServerPlatform) {
			$indexQuery = "select
			i.name as IndexName,
			o.name as TableName
			from sys.indexes i
			join sys.objects o on i.object_id = o.object_id
			join sys.index_columns ic on ic.object_id = i.object_id
			and ic.index_id = i.index_id
			join sys.columns co on co.object_id = i.object_id
			and co.column_id = ic.column_id
			where i.[type] = 2
			and i.is_primary_key = 0
			and o.[type] = 'U'
			and co.[name] = '$columnName'
			and o.name = '$tableName'
			order by o.[name], i.[name], ic.is_included_column, ic.key_ordinal;";
			$indexStmt = $connection->executeQuery($indexQuery);
			foreach($indexStmt->fetchAll() as $index) {
				$this->dropIndexByName($connection, $index['TableName'], $index['IndexName']);
			}
		} else if($platform instanceof MySqlPlatform) {
			$indexQuery = "SHOW INDEX FROM $tableName WHERE Column_name = '$columnName'";
			$indexStmt = $connection->executeQuery($indexQuery);
			foreach($indexStmt->fetchAll() as $index) {
				$this->dropIndexByName($connection, $index['Table'], $index['Key_name']);
			}
		} else {
			throw new \LogicException('Method "' . __METHOD__ . '" not implemented for this platform');
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \StingerSoft\DoctrineCommons\Utils\DoctrineFunctionsInterface::dropIndexByName()
	 */
	public function dropIndexByName(Connection $connection, string $tableName, string $indexName): void {
		$platform = $connection->getDatabasePlatform();
		if($platform instanceof SQLServerPlatform) {
			if($this->hasIndex($connection, $tableName, $indexName)) {
				$connection->executeQuery('DROP INDEX ' . $indexName . ' ON ' . $tableName);
			}
		} else if($platform instanceof MySqlPlatform) {
			$connection->executeQuery('ALTER TABLE ' . $tableName . ' DROP INDEX ' . $indexName);
		} else {
			throw new \LogicException('Method "' . __METHOD__ . '" not implemented for this platform');
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \StingerSoft\DoctrineCommons\Utils\DoctrineFunctionsInterface::hasIndex()
	 */
	public function hasIndex(Connection $connection, string $tableName, string $indexName): bool {
		$platform = $connection->getDatabasePlatform();
		if($platform instanceof SQLServerPlatform) {
			return $connection->executeQuery("SELECT COUNT(*) FROM sys.indexes WHERE name='" . $indexName . "' AND object_id = OBJECT_ID('" . $tableName . "')")->fetch(\PDO::FETCH_COLUMN) > 0;
		}
		throw new \LogicException('Method "' . __METHOD__ . '" not implemented for this platform');
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \StingerSoft\DoctrineCommons\Utils\DoctrineFunctionsInterface::hasForeignKey()
	 */
	public function hasForeignKey(Connection $connection, string $tableName, string $columnName): bool {
		$platform = $connection->getDatabasePlatform();
		if($platform instanceof MySqlPlatform) {
			$foreignKeyQuery = "SELECT DISTINCT TABLE_NAME, CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = '$tableName' AND COLUMN_NAME = '$columnName'";
			$foreignKeyStmt = $connection->executeQuery($foreignKeyQuery);
			return count($foreignKeyStmt->fetchAll()) > 0;
		}
		if($platform instanceof SQLServerPlatform) {
			$foreignKeyQuery = "SELECT f.name, OBJECT_NAME(f.parent_object_id) TableName, COL_NAME(fc.parent_object_id,fc.parent_column_id) ColName FROM sys.foreign_keys AS f INNER JOIN sys.foreign_key_columns AS fc ON f.OBJECT_ID = fc.constraint_object_id INNER JOIN sys.tables t ON t.OBJECT_ID = fc.parent_object_id WHERE OBJECT_NAME (f.parent_object_id) = '$tableName' AND COL_NAME(fc.parent_object_id,fc.parent_column_id) = '$columnName'";
			$foreignKeyStmt = $connection->executeQuery($foreignKeyQuery);
			return count($foreignKeyStmt->fetchAll()) > 0;
		}
		throw new \LogicException('Method "' . __METHOD__ . '" not implemented for this platform');
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \StingerSoft\DoctrineCommons\Utils\DoctrineFunctionsInterface::dropForeignKey()
	 */
	public function dropForeignKey(Connection $connection, string $tableName, string $columnName): void {
		$platform = $connection->getDatabasePlatform();
		if($platform instanceof MySqlPlatform) {
			$foreignKeyQuery = "SELECT DISTINCT TABLE_NAME, CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = '$tableName' AND COLUMN_NAME = '$columnName'";
			$foreignKeyStmt = $connection->executeQuery($foreignKeyQuery);
			foreach($foreignKeyStmt->fetchAll() as $index) {
				$this->dropForeignKeyByName($connection, $index['TABLE_NAME'], $index['CONSTRAINT_NAME']);
			}
			return;
		}
		if($platform instanceof SQLServerPlatform) {
			$foreignKeyQuery = "SELECT f.name, OBJECT_NAME(f.parent_object_id) TableName, COL_NAME(fc.parent_object_id,fc.parent_column_id) ColName FROM sys.foreign_keys AS f INNER JOIN sys.foreign_key_columns AS fc ON f.OBJECT_ID = fc.constraint_object_id INNER JOIN sys.tables t ON t.OBJECT_ID = fc.parent_object_id WHERE OBJECT_NAME (f.parent_object_id) = '$tableName' AND COL_NAME(fc.parent_object_id,fc.parent_column_id) = '$columnName'";
			$foreignKeyStmt = $connection->executeQuery($foreignKeyQuery);
			foreach($foreignKeyStmt->fetchAll() as $index) {
				$this->dropForeignKeyByName($connection, $index['TableName'], $index['name']);
			}
			return;
		}
		throw new \LogicException('Method "' . __METHOD__ . '" not implemented for this platform');
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \StingerSoft\DoctrineCommons\Utils\DoctrineFunctionsInterface::dropForeignKeyByName()
	 */
	public function dropForeignKeyByName(Connection $connection, string $tableName, string $keyName): void {
		$platform = $connection->getDatabasePlatform();
		if($platform instanceof MySqlPlatform) {
			$connection->executeQuery('ALTER TABLE ' . $tableName . ' DROP FOREIGN KEY ' . $keyName);
			return;
		}
		if($platform instanceof SQLServerPlatform) {
			$connection->executeQuery('ALTER TABLE ' . $tableName . ' DROP CONSTRAINT ' . $keyName);
			return;
		}
		throw new \LogicException('Method "' . __METHOD__ . '" not implemented for this platform');
	}

	/**
	 * Returns all management class metadata
	 *
	 * @return ClassMetadata[]
	 */
	protected function getAllMetadata($managerName = null) {
		if(!$this->allMetadata) {
			$this->allMetadata = $this->registry->getManager($managerName)->getMetadataFactory()->getAllMetadata();
		}
		return $this->allMetadata;
	}

	/**
	 * Generates a short name from the entity FQN
	 *
	 * @param string $entity
	 * @return string
	 */
	protected function getShortClassName($entity) {
		$classParts = explode('\\', $entity);
		return array_pop($classParts);
	}
}