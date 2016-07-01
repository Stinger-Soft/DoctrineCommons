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

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

class TablePrefixServiceTest extends \PHPUnit_Framework_TestCase {

	public static $assocMappingBefore = array(
		'groups' => array(
			'type' => ClassMetadataInfo::MANY_TO_MANY,
			'joinTable' => array(
				'name' => 'user_group',
				'prefixed' => false 
			) 
		),
		'roles' => array(
			'type' => ClassMetadataInfo::MANY_TO_MANY,
			'joinTable' => array(
				'name' => 'user_roles',
				'prefixed' => true 
			) 
		),
		'addresses' => array(
			'type' => ClassMetadataInfo::MANY_TO_MANY,
			'joinTable' => array(
				'name' => 'user_addresses' 
			) 
		) 
	);

	public static $assocMappingAfter = array(
		'groups' => array(
			'type' => ClassMetadataInfo::MANY_TO_MANY,
			'joinTable' => array(
				'name' => 'platform_user_group',
				'prefixed' => true 
			) 
		),
		'roles' => array(
			'type' => ClassMetadataInfo::MANY_TO_MANY,
			'joinTable' => array(
				'name' => 'user_roles',
				'prefixed' => true 
			) 
		),
		'addresses' => array(
			'type' => ClassMetadataInfo::MANY_TO_MANY,
			'joinTable' => array(
				'name' => 'platform_user_addresses',
				'prefixed' => true 
			) 
		) 
	);

	/**
	 *
	 * @var TablePrefixService
	 */
	protected $prefixService;

	public function setUp() {
		$this->prefixService = new TablePrefixService();
	}

	public function testService() {
		$this->assertInstanceOf('StingerSoft\DoctrineCommons\Services\TablePrefixService', $this->prefixService);
	}

	public function testSubscribedEvents() {
		$this->assertArraySubset(array(
			'loadClassMetadata' 
		), $this->prefixService->getSubscribedEvents());
	}

	public function testSingleInheritanceWithoutRoot() {
		$cm = $this->mockEventArgs();
		$cm->method('isInheritanceTypeSingleTable')->will($this->returnValue(true));
		$cm->method('isRootEntity')->will($this->returnValue(false));
		
		$args = new LoadClassMetadataEventArgs($cm, $this->mockEntityManager());
		$this->assertNull($this->prefixService->loadClassMetadata($args));
		$this->assertEquals($cm->associationMappings, self::$assocMappingBefore);
	}

	public function testloadClassMetadata() {
		$cm = $this->mockEventArgs();
		$cm->method('isInheritanceTypeSingleTable')->will($this->returnValue(true));
		$cm->method('isRootEntity')->will($this->returnValue(true));
		
		$args = new LoadClassMetadataEventArgs($cm, $this->mockEntityManager());
		$this->assertNull($this->prefixService->loadClassMetadata($args));
		$this->assertEquals($cm->associationMappings, self::$assocMappingAfter);
	}

	public function testInvalidNamespace() {
		$cm = $this->mockEventArgs();
		$cm->method('isInheritanceTypeSingleTable')->will($this->returnValue(true));
		$cm->method('isRootEntity')->will($this->returnValue(true));
		$cm->namespace = 'Test';
		
		$args = new LoadClassMetadataEventArgs($cm, $this->mockEntityManager());
		$this->assertNull($this->prefixService->loadClassMetadata($args));
		$this->assertEquals($cm->associationMappings, self::$assocMappingBefore);
	}

	protected function mockEventArgs() {
		$cm = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')->setMethods(array(
			'getAssociationMappings',
			'getEntityManager',
			'isInheritanceTypeSingleTable',
			'isRootEntity'
		))->setConstructorArgs(array(
			'StingerSoftPlatform:User' 
		))->getMock();
		$cm->namespace = 'StingerSoft\PlatformBundle\Entity\User';
		$cm->associationMappings = self::$assocMappingBefore;
		$cm->method('getAssociationMappings')->will($this->returnValue($cm->associationMappings));
		$cm->method('getEntityManager')->will($this->returnValue($this->mockEntityManager()));
		// $cm->method('getConnection')->will($this->returnSelf());
		// $cm->method('getDatabasePlatform')->will($this->returnValue(null));
		return $cm;
	}

	protected function mockEntityManager() {
		$em = $this->getMockBuilder(EntityManager::class)->setMethods(array(
			'getConnection',
			'getDatabasePlatform' 
		))->disableOriginalConstructor()->getMockForAbstractClass();
		$em->method('getConnection')->will($this->returnSelf());
		$em->method('getDatabasePlatform')->will($this->returnValue($this->mockMysqlPlatform()));
		return $em;
	}

	protected function mockMysqlPlatform() {
		return $this->getMockBuilder(MySqlPlatform::class)->disableOriginalConstructor()->getMock();
	}
}