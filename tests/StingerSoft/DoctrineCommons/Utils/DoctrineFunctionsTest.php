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

use StingerSoft\DoctrineCommons\AbstractORMGedmoTestCase;
use StingerSoft\DoctrineCommons\Fixtures\ORM\SoftdeletableCategory;
use StingerSoft\DoctrineCommons\Fixtures\ORM\Blog;

class DoctrineFunctionsTest extends AbstractORMGedmoTestCase {
	
	/**
	 * @return DoctrineFunctionsInterface
	 */
	protected function getDoctrineService(){
		$service = new DoctrineFunctions($this->getMockDoctrineRegistry(), $this->getTranslatorMock());
		return $service;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	public function setUp() {
		parent::setUp();
		$this->getMockSqliteEntityManager();
	}

	public function testUnproxify() {
		$category = new SoftdeletableCategory();
		$category->setTitle('NotDeleted');
		$this->em->persist($category);
		
		$blog = new Blog();
		$blog->setTitle('blog1');
		$blog->setCategory($category);
		$this->em->persist($blog);
		$this->em->flush();
		
		$blog2 = new Blog();
		$blog2->setTitle('blog2');
		$blog2->setCategory($category);
		$this->em->persist($blog2);
		$this->em->flush();
		$this->em->clear();
		
		$blog = $this->em->getRepository(Blog::class)->findOneBy(array('title' => 'blog1'));
		$category = $blog->getCategory();
		
		$this->assertNotNull($category);
		$this->assertInstanceOf('\Doctrine\ORM\Proxy\Proxy', $category);
		
		$unproxyCategory = $this->getDoctrineService()->unproxifyFilter($category);
		$this->assertNotNull($unproxyCategory);
		$this->assertInstanceOf(SoftdeletableCategory::class, $unproxyCategory);
		
		$this->assertNotNull($category);
		$this->assertInstanceOf('\Doctrine\ORM\Proxy\Proxy', $category);
		
		$blog2 = $this->em->getRepository(Blog::class)->findOneBy(array('title' => 'blog2'));
		$category = $blog2->getCategory();
		$this->assertNotNull($category);
	}

	protected function getUsedEntityFixtures() {
		return array(
			Blog::class,
			SoftdeletableCategory::class 
		);
	}
}