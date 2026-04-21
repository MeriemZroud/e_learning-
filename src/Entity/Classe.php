<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ClasseRepository;

#[ORM\Entity(repositoryClass: ClasseRepository::class)]
#[ORM\Table(name: 'classes')]
class Classe
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

    #[ORM\ManyToOne(targetEntity: AcademicYear::class, inversedBy: 'classes')]
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

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $grade_level = null;

    public function getGrade_level(): ?string
    {
        return $this->grade_level;
    }

    public function setGrade_level(string $grade_level): self
    {
        $this->grade_level = $grade_level;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $section = null;

    public function getSection(): ?string
    {
        return $this->section;
    }

    public function setSection(?string $section): self
    {
        $this->section = $section;
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

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $created_by = null;

    public function getCreated_by(): ?int
    {
        return $this->created_by;
    }

    public function setCreated_by(?int $created_by): self
    {
        $this->created_by = $created_by;
        return $this;
    }

    #[ORM\OneToOne(targetEntity: ClassEnrollment::class, mappedBy: 'classe')]
    private ?ClassEnrollment $classEnrollment = null;

    public function getClassEnrollment(): ?ClassEnrollment
    {
        return $this->classEnrollment;
    }

    public function setClassEnrollment(?ClassEnrollment $classEnrollment): self
    {
        $this->classEnrollment = $classEnrollment;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: ForumPost::class, mappedBy: 'classe')]
    private Collection $forumPosts;

    /**
     * @return Collection<int, ForumPost>
     */
    public function getForumPosts(): Collection
    {
        if (!$this->forumPosts instanceof Collection) {
            $this->forumPosts = new ArrayCollection();
        }
        return $this->forumPosts;
    }

    public function addForumPost(ForumPost $forumPost): self
    {
        if (!$this->getForumPosts()->contains($forumPost)) {
            $this->getForumPosts()->add($forumPost);
        }
        return $this;
    }

    public function removeForumPost(ForumPost $forumPost): self
    {
        $this->getForumPosts()->removeElement($forumPost);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'classe')]
    private Collection $notifications;

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        if (!$this->notifications instanceof Collection) {
            $this->notifications = new ArrayCollection();
        }
        return $this->notifications;
    }

    public function addNotification(Notification $notification): self
    {
        if (!$this->getNotifications()->contains($notification)) {
            $this->getNotifications()->add($notification);
        }
        return $this;
    }

    public function removeNotification(Notification $notification): self
    {
        $this->getNotifications()->removeElement($notification);
        return $this;
    }

    #[ORM\OneToOne(targetEntity: SubjectSection::class, mappedBy: 'classe')]
    private ?SubjectSection $subjectSection = null;

    public function __construct()
    {
        $this->forumPosts = new ArrayCollection();
        $this->notifications = new ArrayCollection();
    }

    public function getSubjectSection(): ?SubjectSection
    {
        return $this->subjectSection;
    }

    public function setSubjectSection(?SubjectSection $subjectSection): self
    {
        $this->subjectSection = $subjectSection;
        return $this;
    }

    public function getGradeLevel(): ?string
    {
        return $this->grade_level;
    }

    public function setGradeLevel(string $grade_level): static
    {
        $this->grade_level = $grade_level;

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

    public function getCreatedAt(): ?\DateTime
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTime $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getCreatedBy(): ?int
    {
        return $this->created_by;
    }

    public function setCreatedBy(?int $created_by): static
    {
        $this->created_by = $created_by;

        return $this;
    }

}
