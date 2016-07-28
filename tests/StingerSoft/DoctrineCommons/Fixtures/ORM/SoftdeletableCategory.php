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
namespace StingerSoft\DoctrineCommons\Fixtures\ORM;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 */
class SoftdeletableCategory {

	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="IDENTITY")
	 */
	private $id;

	/**
	 * @ORM\Column
	 */
	private $title;

	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	private $deletedAt;

	/**
	 * @ORM\OneToMany(targetEntity="Blog", mappedBy="category")
	 */
	private $blogs;

	public function getId() {
		return $this->id;
	}

	public function setTitle($title) {
		$this->title = $title;
	}

	public function getTitle() {
		return $this->title;
	}

	public function getDeletedAt() {
		return $this->deletedAt;
	}

	public function setDeletedAt($deletedAt) {
		$this->deletedAt = $deletedAt;
	}
}