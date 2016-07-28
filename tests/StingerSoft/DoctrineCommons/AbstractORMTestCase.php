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
namespace StingerSoft\DoctrineCommons;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\DefaultNamingStrategy;
use Doctrine\ORM\Mapping\DefaultQuoteStrategy;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Repository\DefaultRepositoryFactory;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Common\Persistence\AbstractManagerRegistry;

abstract class AbstractORMTestCase extends AbstractDatabaseTestCase {

	/**
	 *
	 * @var EntityManager
	 */
	protected $em;

	/**
	 * EntityManager mock object together with
	 * annotation mapping driver and pdo_sqlite
	 * database in memory
	 *
	 * @param EventManager $evm        	
	 *
	 * @return EntityManager
	 */
	protected function getMockSqliteEntityManager(EventManager $evm = null, Configuration $config = null) {
		$conn = array(
			'driver' => 'pdo_sqlite',
			'memory' => true 
		);
		$config = null === $config ? $this->getMockAnnotatedConfig() : $config;
		$em = EntityManager::create($conn, $config, $evm ?: $this->getEventManager());
		$schema = array_map(function ($class) use ($em) {
			return $em->getClassMetadata($class);
		}, (array)$this->getUsedEntityFixtures());
		$schemaTool = new SchemaTool($em);
		$schemaTool->dropSchema(array());
		$schemaTool->createSchema($schema);
		return $this->em = $em;
	}
	
	/**
	 * @return AbstractManagerRegistry
	 */
	protected function getMockDoctrineRegistry(){
		$mb = $this->getMockBuilder(AbstractManagerRegistry::class)->disableOriginalConstructor()->setMethods(array('getManagerForClass', 'getManager'))->getMockForAbstractClass();
		$mb->method('getManagerForClass')->willReturn($this->em);
		$mb->method('getManager')->willReturn($this->em);
		return $mb;
	}

	/**
	 * 
	 * @return \Doctrine\Common\EventManager
	 */
	protected function getEventManager() {
		return new EventManager();
	}

	/**
	 * Get annotation mapping configuration
	 *
	 * @return \Doctrine\ORM\Configuration
	 */
	protected function getMockAnnotatedConfig() {
		// We need to mock every method except the ones which
		// handle the filters
		$configurationClass = 'Doctrine\ORM\Configuration';
		$refl = new \ReflectionClass($configurationClass);
		$methods = $refl->getMethods();
		$mockMethods = array();
		foreach($methods as $method) {
			if($method->name !== 'addFilter' && $method->name !== 'getFilterClassName') {
				$mockMethods[] = $method->name;
			}
		}
		$config = $this->getMockBuilder($configurationClass)->setMethods($mockMethods)->getMock();
		$config->expects($this->once())->method('getProxyDir')->will($this->returnValue(__DIR__ . '/../../temp'));
		$config->expects($this->once())->method('getProxyNamespace')->will($this->returnValue('Proxy'));
		$config->expects($this->any())->method('getDefaultQueryHints')->will($this->returnValue(array()));
		$config->expects($this->once())->method('getAutoGenerateProxyClasses')->will($this->returnValue(true));
		$config->expects($this->once())->method('getClassMetadataFactoryName')->will($this->returnValue('Doctrine\\ORM\\Mapping\\ClassMetadataFactory'));
		$mappingDriver = $this->getMetadataDriverImplementation();
		$config->expects($this->any())->method('getMetadataDriverImpl')->will($this->returnValue($mappingDriver));
		$config->expects($this->any())->method('getDefaultRepositoryClassName')->will($this->returnValue('Doctrine\\ORM\\EntityRepository'));
		$config->expects($this->any())->method('getQuoteStrategy')->will($this->returnValue(new DefaultQuoteStrategy()));
		$config->expects($this->any())->method('getNamingStrategy')->will($this->returnValue(new DefaultNamingStrategy()));
		$config->expects($this->once())->method('getRepositoryFactory')->will($this->returnValue(new DefaultRepositoryFactory()));
		return $config;
	}

	/**
	 * Creates default mapping driver
	 *
	 * @return \Doctrine\ORM\Mapping\Driver\Driver
	 */
	protected function getMetadataDriverImplementation() {
		return new AnnotationDriver($_ENV['annotation_reader'], $this->getPaths());
	}

	protected function getPaths() {
		return array();
	}
}