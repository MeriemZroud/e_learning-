<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\TermRepository;

#[ORM\Entity(repositoryClass: TermRepository::class)]
#[ORM\Table(name: 'terms')]
class Term
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

    #[ORM\ManyToOne(targetEntity: AcademicYear::class, inversedBy: 'terms')]
    #[ORM\JoinColumn(name: 'academic_year_id', referencedColumnName: 'id')]
    private ?AcademicYear $academicYear = null;

    public function getAcademicYear(): ?AcademicYear
    {
        return $this->academicYear;
    }

    public function setAcademicYear(?AcademicYear $academicYear): self
    {
        $this->academicYear = $academicYear;
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

    #[ORM\Column(type: 'date', nullable: false)]
    private ?\DateTimeInterface $start_date = null;

    public function getStart_date(): ?\DateTimeInterface
    {
        return $this->start_date;
    }

    public function setStart_date(\DateTimeInterface $start_date): self
    {
        $this->start_date = $start_date;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: false)]
    private ?\DateTimeInterface $end_date = null;

    public function getEnd_date(): ?\DateTimeInterface
    {
        return $this->end_date;
    }

    public function setEnd_date(\DateTimeInterface $end_date): self
    {
        $this->end_date = $end_date;
        return $this;
    }

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $is_current = null;

    public function is_current(): ?bool
    {
        return $this->is_current;
    }

    public function setIs_current(?bool $is_current): self
    {
        $this->is_current = $is_current;
        return $this;
    }

    #[ORM\OneToOne(targetEntity: SubjectSection::class, mappedBy: 'term')]
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

    public function getStartDate(): ?\DateTime
    {
        return $this->start_date;
    }

    public function setStartDate(\DateTime $start_date): static
    {
        $this->start_date = $start_date;

        return $this;
    }

    public function getEndDate(): ?\DateTime
    {
        return $this->end_date;
    }

    public function setEndDate(\DateTime $end_date): static
    {
        $this->end_date = $end_date;

        return $this;
    }

    public function isCurrent(): ?bool
    {
        return $this->is_current;
    }

    public function setIsCurrent(?bool $is_current): static
    {
        $this->is_current = $is_current;

        return $this;
    }

}
