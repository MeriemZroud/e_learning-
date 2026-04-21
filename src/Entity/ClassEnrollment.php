<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ClassEnrollmentRepository;

#[ORM\Entity(repositoryClass: ClassEnrollmentRepository::class)]
#[ORM\Table(name: 'class_enrollments')]
class ClassEnrollment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    #[ORM\OneToOne(targetEntity: Classe::class, inversedBy: 'classEnrollment')]
    #[ORM\JoinColumn(name: 'class_id', referencedColumnName: 'id', unique: true)]
    private ?Classe $classe = null;

    public function getClasse(): ?Classe
    {
        return $this->classe;
    }

    public function setClasse(?Classe $classe): self
    {
        $this->classe = $classe;
        return $this;
    }

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'classEnrollment')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', unique: true)]
    private ?User $user = null;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: false)]
    private ?\DateTimeInterface $enrolled_at = null;

    public function getEnrolled_at(): ?\DateTimeInterface
    {
        return $this->enrolled_at;
    }

    public function setEnrolled_at(\DateTimeInterface $enrolled_at): self
    {
        $this->enrolled_at = $enrolled_at;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $left_at = null;

    public function getLeft_at(): ?\DateTimeInterface
    {
        return $this->left_at;
    }

    public function setLeft_at(?\DateTimeInterface $left_at): self
    {
        $this->left_at = $left_at;
        return $this;
    }

    public function getEnrolledAt(): ?\DateTime
    {
        return $this->enrolled_at;
    }

    public function setEnrolledAt(\DateTime $enrolled_at): static
    {
        $this->enrolled_at = $enrolled_at;

        return $this;
    }

    public function getLeftAt(): ?\DateTime
    {
        return $this->left_at;
    }

    public function setLeftAt(?\DateTime $left_at): static
    {
        $this->left_at = $left_at;

        return $this;
    }

}
