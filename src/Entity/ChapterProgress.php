<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ChapterProgressRepository;

#[ORM\Entity(repositoryClass: ChapterProgressRepository::class)]
#[ORM\Table(name: 'chapter_progress')]
class ChapterProgress
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

    #[ORM\OneToOne(targetEntity: Chapter::class, inversedBy: 'chapterProgress')]
    #[ORM\JoinColumn(name: 'chapter_id', referencedColumnName: 'id', unique: true)]
    private ?Chapter $chapter = null;

    public function getChapter(): ?Chapter
    {
        return $this->chapter;
    }

    public function setChapter(?Chapter $chapter): self
    {
        $this->chapter = $chapter;
        return $this;
    }

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'chapterProgress')]
    #[ORM\JoinColumn(name: 'student_id', referencedColumnName: 'id', unique: true)]
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
    private ?\DateTimeInterface $last_accessed_at = null;

    public function getLast_accessed_at(): ?\DateTimeInterface
    {
        return $this->last_accessed_at;
    }

    public function setLast_accessed_at(?\DateTimeInterface $last_accessed_at): self
    {
        $this->last_accessed_at = $last_accessed_at;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $completed_at = null;

    public function getCompleted_at(): ?\DateTimeInterface
    {
        return $this->completed_at;
    }

    public function setCompleted_at(?\DateTimeInterface $completed_at): self
    {
        $this->completed_at = $completed_at;
        return $this;
    }

    public function getLastAccessedAt(): ?\DateTime
    {
        return $this->last_accessed_at;
    }

    public function setLastAccessedAt(?\DateTime $last_accessed_at): static
    {
        $this->last_accessed_at = $last_accessed_at;

        return $this;
    }

    public function getCompletedAt(): ?\DateTime
    {
        return $this->completed_at;
    }

    public function setCompletedAt(?\DateTime $completed_at): static
    {
        $this->completed_at = $completed_at;

        return $this;
    }

}
