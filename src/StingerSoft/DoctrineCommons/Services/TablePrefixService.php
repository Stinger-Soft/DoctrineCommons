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
namespace StingerSoft\DoctrineCommons\Services;

use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;

/**
 * This service adds the bundle name as a prefix to every table name to avoid name collision between different bundles
 */
class TablePrefixService implements \Doctrine\Common\EventSubscriber {

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \Doctrine\Common\EventSubscriber::getSubscribedEvents()
	 */
	public function getSubscribedEvents() {
		return array(
			'loadClassMetadata' 
		);
	}

	/**
	 * Event listeners
	 *
	 * @param LoadClassMetadataEventArgs $args        	
	 */
	public function loadClassMetadata(LoadClassMetadataEventArgs $args) {
		$classMetadata = $args->getClassMetadata();
		
		// Do not re-apply the prefix in an inheritance hierarchy.
		if($classMetadata->isInheritanceTypeSingleTable() && !$classMetadata->isRootEntity()) {
			return;
		}
		
		$prefix = $classMetadata->namespace;
		preg_match('/([^\\\\]+)Bundle/i', $classMetadata->namespace, $prefix);
		if(!preg_match('/([^\\\\]+)Bundle/i', $classMetadata->namespace, $prefix)) {
			return;
		}
		if(count($prefix) != 2) {
			return;
		}
		$prefix = strtolower($prefix[1]) . '_';
		
		$classMetadata->setPrimaryTable(array(
			'name' => $prefix . $classMetadata->getTableName() 
		));
		
		if($args->getEntityManager()->getConnection()->getDatabasePlatform() instanceof SqlitePlatform && array_key_exists('indexes', $classMetadata->table)) {
			$indexes = array();
			foreach($classMetadata->table['indexes'] as $name => $params) {
				$indexes[$prefix . $classMetadata->getTableName() . '_' . $name] = $params;
			}
			$classMetadata->table['indexes'] = $indexes;
		}
		
		foreach($classMetadata->getAssociationMappings() as $fieldName => $mapping) {
			if($mapping['type'] == \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_MANY && isset($classMetadata->associationMappings[$fieldName]['joinTable']['name'])) {
				if(isset($classMetadata->associationMappings[$fieldName]['joinTable']['prefixed']) && $classMetadata->associationMappings[$fieldName]['joinTable']['prefixed']) {
					continue;
				}
				
				$mappedTableName = $classMetadata->associationMappings[$fieldName]['joinTable']['name'];
				$classMetadata->associationMappings[$fieldName]['joinTable']['name'] = $prefix . $mappedTableName;
				$classMetadata->associationMappings[$fieldName]['joinTable']['prefixed'] = true;
			}
		}
	}
}