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

namespace StingerSoft\DoctrineCommons\Fixtures\ORM;

use Doctrine\Common\Collections\Collection;
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
	private ?int $id;

	/**
	 * @ORM\Column
	 */
	private ?string $title;

	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	private ?\DateTimeInterface $deletedAt;

	/**
	 * @var Collection|array
	 * @ORM\OneToMany(targetEntity="Blog", mappedBy="category")
	 */
	private $blogs;

	public function getId(): ?int {
		return $this->id;
	}

	public function setTitle(?string $title): void {
		$this->title = $title;
	}

	public function getTitle(): ?string {
		return $this->title;
	}

	public function getDeletedAt(): ?\DateTimeInterface {
		return $this->deletedAt;
	}

	public function setDeletedAt(?\DateTimeInterface $deletedAt): void {
		$this->deletedAt = $deletedAt;
	}

	public function getEntityLabel(): string {
		return 'DeleteableCategory';
	}

	public function getEntityLabelTranslationDomain(): string {
		return '';
	}
}
