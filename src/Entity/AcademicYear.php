<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\AcademicYearRepository;

#[ORM\Entity(repositoryClass: AcademicYearRepository::class)]
#[ORM\Table(name: 'academic_years')]
class AcademicYear
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

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $status = null;

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $created_at = null;

    public function getCreated_at(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreated_at(?\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Classe::class, mappedBy: 'academicYear')]
    private Collection $classes;

    /**
     * @return Collection<int, Classe>
     */
    public function getClasses(): Collection
    {
        if (!$this->classes instanceof Collection) {
            $this->classes = new ArrayCollection();
        }
        return $this->classes;
    }

    public function addClasse(Classe $classe): self
    {
        if (!$this->getClasses()->contains($classe)) {
            $this->getClasses()->add($classe);
        }
        return $this;
    }

    public function removeClasse(Classe $classe): self
    {
        $this->getClasses()->removeElement($classe);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Term::class, mappedBy: 'academicYear')]
    private Collection $terms;

    public function __construct()
    {
        $this->classes = new ArrayCollection();
        $this->terms = new ArrayCollection();
    }

    /**
     * @return Collection<int, Term>
     */
    public function getTerms(): Collection
    {
        if (!$this->terms instanceof Collection) {
            $this->terms = new ArrayCollection();
        }
        return $this->terms;
    }

    public function addTerm(Term $term): self
    {
        if (!$this->getTerms()->contains($term)) {
            $this->getTerms()->add($term);
        }
        return $this;
    }

    public function removeTerm(Term $term): self
    {
        $this->getTerms()->removeElement($term);
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

    public function getCreatedAt(): ?\DateTime
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTime $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function addClass(Classe $class): static
    {
        if (!$this->classes->contains($class)) {
            $this->classes->add($class);
            $class->setAcademicYear($this);
        }

        return $this;
    }

    public function removeClass(Classe $class): static
    {
        if ($this->classes->removeElement($class)) {
            // set the owning side to null (unless already changed)
            if ($class->getAcademicYear() === $this) {
                $class->setAcademicYear(null);
            }
        }

        return $this;
    }

}
