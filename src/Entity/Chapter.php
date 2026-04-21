<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ChapterRepository;

#[ORM\Entity(repositoryClass: ChapterRepository::class)]
#[ORM\Table(name: 'chapters')]
class Chapter
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

    #[ORM\ManyToOne(targetEntity: SubjectSection::class, inversedBy: 'chapters')]
    #[ORM\JoinColumn(name: 'section_id', referencedColumnName: 'id')]
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

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $title = null;

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
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

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $sort_order = null;

    public function getSort_order(): ?int
    {
        return $this->sort_order;
    }

    public function setSort_order(?int $sort_order): self
    {
        $this->sort_order = $sort_order;
        return $this;
    }

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $is_published = null;

    public function is_published(): ?bool
    {
        return $this->is_published;
    }

    public function setIs_published(?bool $is_published): self
    {
        $this->is_published = $is_published;
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

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updated_at = null;

    public function getUpdated_at(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdated_at(?\DateTimeInterface $updated_at): self
    {
        $this->updated_at = $updated_at;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Assignment::class, mappedBy: 'chapter')]
    private Collection $assignments;

    /**
     * @return Collection<int, Assignment>
     */
    public function getAssignments(): Collection
    {
        if (!$this->assignments instanceof Collection) {
            $this->assignments = new ArrayCollection();
        }
        return $this->assignments;
    }

    public function addAssignment(Assignment $assignment): self
    {
        if (!$this->getAssignments()->contains($assignment)) {
            $this->getAssignments()->add($assignment);
        }
        return $this;
    }

    public function removeAssignment(Assignment $assignment): self
    {
        $this->getAssignments()->removeElement($assignment);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: ChapterItem::class, mappedBy: 'chapter')]
    private Collection $chapterItems;

    /**
     * @return Collection<int, ChapterItem>
     */
    public function getChapterItems(): Collection
    {
        if (!$this->chapterItems instanceof Collection) {
            $this->chapterItems = new ArrayCollection();
        }
        return $this->chapterItems;
    }

    public function addChapterItem(ChapterItem $chapterItem): self
    {
        if (!$this->getChapterItems()->contains($chapterItem)) {
            $this->getChapterItems()->add($chapterItem);
        }
        return $this;
    }

    public function removeChapterItem(ChapterItem $chapterItem): self
    {
        $this->getChapterItems()->removeElement($chapterItem);
        return $this;
    }

    #[ORM\OneToOne(targetEntity: ChapterProgress::class, mappedBy: 'chapter')]
    private ?ChapterProgress $chapterProgress = null;

    public function __construct()
    {
        $this->assignments = new ArrayCollection();
        $this->chapterItems = new ArrayCollection();
    }

    public function getChapterProgress(): ?ChapterProgress
    {
        return $this->chapterProgress;
    }

    public function setChapterProgress(?ChapterProgress $chapterProgress): self
    {
        $this->chapterProgress = $chapterProgress;
        return $this;
    }

    public function getSortOrder(): ?int
    {
        return $this->sort_order;
    }

    public function setSortOrder(?int $sort_order): static
    {
        $this->sort_order = $sort_order;

        return $this;
    }

    public function isPublished(): ?bool
    {
        return $this->is_published;
    }

    public function setIsPublished(?bool $is_published): static
    {
        $this->is_published = $is_published;

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

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTime $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

}
