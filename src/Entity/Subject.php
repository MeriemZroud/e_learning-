<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\SubjectRepository;

#[ORM\Entity(repositoryClass: SubjectRepository::class)]
#[ORM\Table(name: 'subjects')]
class Subject
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

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $subject_code = null;

    public function getSubject_code(): ?string
    {
        return $this->subject_code;
    }

    public function setSubject_code(string $subject_code): self
    {
        $this->subject_code = $subject_code;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $name = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $is_active = null;

    public function is_active(): ?bool
    {
        return $this->is_active;
    }

    public function setIs_active(?bool $is_active): self
    {
        $this->is_active = $is_active;
        return $this;
    }

    #[ORM\OneToOne(targetEntity: SubjectSection::class, mappedBy: 'subject')]
    private ?SubjectSection $subjectSection = null;

    public function getSubjectSection(): ?SubjectSection
    {
        return $this->subjectSection;
    }

    public function setSubjectSection(?SubjectSection $subjectSection): self
    {
        $this->subjectSection = $subjectSection;
        return $this;
    }

    public function getSubjectCode(): ?string
    {
        return $this->subject_code;
    }

    public function setSubjectCode(string $subject_code): static
    {
        $this->subject_code = $subject_code;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->is_active;
    }

    public function setIsActive(?bool $is_active): static
    {
        $this->is_active = $is_active;

        return $this;
    }

}
