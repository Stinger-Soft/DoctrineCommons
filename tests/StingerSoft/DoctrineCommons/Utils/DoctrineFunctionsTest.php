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
use StingerSoft\DoctrineCommons\Fixtures\ORM\IconifiedBlog;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;
use StingerSoft\TestBundle\StingerSoftTestBundle;
use StingerSoft\DoctrineCommons\Fixtures\ORM\BlogInterface;

class DoctrineFunctionsTest extends AbstractORMGedmoTestCase {
	
	/**
	 * @return DoctrineFunctionsInterface
	 */
	protected function getDoctrineService($kernel = null){
		$service = new DoctrineFunctions($this->getMockDoctrineRegistry(), $this->getTranslatorMock(), $kernel);
		return $service;
	}
	
	protected function mockKernel($bundles = array()) {
		$kernel = $this->getMockBuilder(KernelInterface::class)->setMethods(array('getBundles'))->getMockForAbstractClass();
		$kernel->method('getBundles')->willReturn($bundles);
		return $kernel;
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
			IconifiedBlog::class,
			SoftdeletableCategory::class 
		);
	}
	
	public function testGetEntityIcon() {
		$blog = new Blog();
		
		$blogIcon = $this->getDoctrineService()->getEntityIcon($blog);
		$this->assertNull($blogIcon);
		$blogIcon = $this->getDoctrineService()->getEntityIcon(get_class($blog));
		$this->assertNull($blogIcon);
		$blogIcon = $this->getDoctrineService()->getEntityIcon($blog, "purpose");
		$this->assertNull($blogIcon);
		$blogIcon = $this->getDoctrineService()->getEntityIcon(get_class($blog), "purpose");
		$this->assertNull($blogIcon);
		
		$iconBlog = new IconifiedBlog();
		$iconBlogIcon = $this->getDoctrineService()->getEntityIcon($iconBlog);
		$this->assertNotNull($iconBlogIcon);
		$this->assertEquals($iconBlogIcon, 'icon');
		$iconBlogIcon = $this->getDoctrineService()->getEntityIcon(get_class($iconBlog));
		$this->assertNotNull($iconBlogIcon);
		$this->assertEquals($iconBlogIcon, 'icon');
		$iconBlogIcon = $this->getDoctrineService()->getEntityIcon($iconBlog, "purpose");
		$this->assertNotNull($iconBlogIcon);
		$this->assertEquals($iconBlogIcon, 'purpose');
		$iconBlogIcon = $this->getDoctrineService()->getEntityIcon(get_class($iconBlog), "purpose");
		$this->assertNotNull($iconBlogIcon);
		$this->assertEquals($iconBlogIcon, 'purpose');
	}
	
	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testGetBundleNameWOKernel() {
		$this->getDoctrineService()->getBundleName('Test');
	}
	
	public function testGetBundleNameUnkownEntity() {
		$name = $this->getDoctrineService($this->mockKernel())->getBundleName('Test');
		$this->assertNull($name);
	}
	
	public function testGetBundleName() {
		$bundles = array('StingerSoftTestBundle' => StingerSoftTestBundle::class);
		$name = $this->getDoctrineService($this->mockKernel($bundles))->getBundleName('StingerSoft\\TestBundle\\Entity\\Test');
		$this->assertEquals('StingerSoftTestBundle', $name);
	}
	
	public function testGetBundleNameForObject() {
		$bundles = array('StingerSoftTestBundle' => StingerSoftTestBundle::class);
		$name = $this->getDoctrineService($this->mockKernel($bundles))->getBundleName(new Blog());
		$this->assertNull($name);
	}
	
	public function testGetEntitiesByParent() {
		$this->assertEmpty($this->getDoctrineService()->getEntitiesByParent(SoftdeletableCategory::class));
		$this->assertContains(IconifiedBlog::class, $this->getDoctrineService()->getEntitiesByParent(Blog::class));
	}
	
	public function testGetEntitiesByInterface() {
		$result = $this->getDoctrineService()->getEntitiesByInterface(BlogInterface::class);
		$this->assertContains(IconifiedBlog::class, $result);
		$this->assertContains(Blog::class, $result);
	}
}