<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\AssignmentRepository;

#[ORM\Entity(repositoryClass: AssignmentRepository::class)]
#[ORM\Table(name: 'assignments')]
class Assignment
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

    #[ORM\ManyToOne(targetEntity: Chapter::class, inversedBy: 'assignments')]
    #[ORM\JoinColumn(name: 'chapter_id', referencedColumnName: 'id')]
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

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $due_date = null;

    public function getDue_date(): ?\DateTimeInterface
    {
        return $this->due_date;
    }

    public function setDue_date(?\DateTimeInterface $due_date): self
    {
        $this->due_date = $due_date;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $submission_type = null;

    public function getSubmission_type(): ?string
    {
        return $this->submission_type;
    }

    public function setSubmission_type(?string $submission_type): self
    {
        $this->submission_type = $submission_type;
        return $this;
    }

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $allow_late_submission = null;

    public function isAllow_late_submission(): ?bool
    {
        return $this->allow_late_submission;
    }

    public function setAllow_late_submission(?bool $allow_late_submission): self
    {
        $this->allow_late_submission = $allow_late_submission;
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

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $type = null;

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    #[ORM\OneToOne(targetEntity: Submission::class, mappedBy: 'assignment')]
    private ?Submission $submission = null;

    public function getSubmission(): ?Submission
    {
        return $this->submission;
    }

    public function setSubmission(?Submission $submission): self
    {
        $this->submission = $submission;
        return $this;
    }

    public function getDueDate(): ?\DateTime
    {
        return $this->due_date;
    }

    public function setDueDate(?\DateTime $due_date): static
    {
        $this->due_date = $due_date;

        return $this;
    }

    public function getSubmissionType(): ?string
    {
        return $this->submission_type;
    }

    public function setSubmissionType(?string $submission_type): static
    {
        $this->submission_type = $submission_type;

        return $this;
    }

    public function isAllowLateSubmission(): ?bool
    {
        return $this->allow_late_submission;
    }

    public function setAllowLateSubmission(?bool $allow_late_submission): static
    {
        $this->allow_late_submission = $allow_late_submission;

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
