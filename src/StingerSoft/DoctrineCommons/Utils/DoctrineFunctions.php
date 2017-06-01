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

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\AbstractManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Proxy\Proxy;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\SQLServerPlatform;

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
	 * @param KernelInterface $kernel
	 * @param EntityManager $em
	 * @param TranslatorInterface $translator
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
	 * @see \Pec\Bundle\PlatformBundle\Commons\DoctrineFunctionsInterface::getEntitiesByInterface()
	 */
	public function getEntitiesByInterface($interface, $groupByBundle = false) {
		return $this->getEntitiesByCallback(function (\ReflectionClass $rc) use ($interface) {
			return array_key_exists($interface, $rc->getInterfaces());
		}, $groupByBundle);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \Pec\Bundle\PlatformBundle\Commons\DoctrineFunctionsInterface::getEntitiesByParent()
	 */
	public function getEntitiesByParent($parent, $groupByBundle = false) {
		return $this->getEntitiesByCallback(function (\ReflectionClass $rc) use ($parent) {
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
	 * @see \Pec\Bundle\PlatformBundle\Commons\DoctrineFunctionsInterface::getHumanReadableEntityName()
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
				return $this->translator->trans(call_user_func(array(
					$entity,
					'getClassLabel' 
				)), array(), call_user_func(array(
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
	 * @see \Pec\Bundle\PlatformBundle\Commons\DoctrineFunctionsInterface::getBundleName()
	 */
	public function getBundleName($entity) {
		if(!$this->kernel) {
			throw new \InvalidArgumentException('You must construct this class with a valid kernel!');
		}
		$bundles = $this->kernel->getBundles();
		if(is_object($entity)) {
			$entity = get_class($entity);
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
			$item = $em->find($class, $object->getId());
			return $item;
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
		$class = array_pop($classParts);
		return $class;
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
		}
	}
}