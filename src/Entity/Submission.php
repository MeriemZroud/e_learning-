<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\SubmissionRepository;

#[ORM\Entity(repositoryClass: SubmissionRepository::class)]
#[ORM\Table(name: 'submissions')]
class Submission
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

    #[ORM\ManyToOne(targetEntity: Assignment::class, inversedBy: 'submissions')]
    #[ORM\JoinColumn(name: 'assignment_id', referencedColumnName: 'id')]
    private ?Assignment $assignment = null;

    public function getAssignment(): ?Assignment
    {
        return $this->assignment;
    }

    public function setAssignment(?Assignment $assignment): self
    {
        $this->assignment = $assignment;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'submissions')]
    #[ORM\JoinColumn(name: 'student_id', referencedColumnName: 'id')]
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

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $submission_text = null;

    public function getSubmission_text(): ?string
    {
        return $this->submission_text;
    }

    public function setSubmission_text(?string $submission_text): self
    {
        $this->submission_text = $submission_text;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $submitted_at = null;

    public function getSubmitted_at(): ?\DateTimeInterface
    {
        return $this->submitted_at;
    }

    public function setSubmitted_at(?\DateTimeInterface $submitted_at): self
    {
        $this->submitted_at = $submitted_at;
        return $this;
    }

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $is_late = null;

    public function is_late(): ?bool
    {
        return $this->is_late;
    }

    public function setIs_late(?bool $is_late): self
    {
        $this->is_late = $is_late;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $feedback = null;

    public function getFeedback(): ?string
    {
        return $this->feedback;
    }

    public function setFeedback(?string $feedback): self
    {
        $this->feedback = $feedback;
        return $this;
    }

    

   

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $reviewed_at = null;

    public function getReviewed_at(): ?\DateTimeInterface
    {
        return $this->reviewed_at;
    }

    public function setReviewed_at(?\DateTimeInterface $reviewed_at): self
    {
        $this->reviewed_at = $reviewed_at;
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

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $grade = null;

    public function getGrade(): ?float
    {
        return $this->grade;
    }

    public function setGrade(?float $grade): self
    {
        $this->grade = $grade;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: SubmissionFile::class, mappedBy: 'submission')]
    private Collection $submissionFiles;

    public function __construct()
    {
        $this->submissionFiles = new ArrayCollection();
    }

    /**
     * @return Collection<int, SubmissionFile>
     */
    public function getSubmissionFiles(): Collection
    {
        if (!$this->submissionFiles instanceof Collection) {
            $this->submissionFiles = new ArrayCollection();
        }
        return $this->submissionFiles;
    }

    public function addSubmissionFile(SubmissionFile $submissionFile): self
    {
        if (!$this->getSubmissionFiles()->contains($submissionFile)) {
            $this->getSubmissionFiles()->add($submissionFile);
        }
        return $this;
    }

    public function removeSubmissionFile(SubmissionFile $submissionFile): self
    {
        $this->getSubmissionFiles()->removeElement($submissionFile);
        return $this;
    }

    public function getSubmissionText(): ?string
    {
        return $this->submission_text;
    }

    public function setSubmissionText(?string $submission_text): static
    {
        $this->submission_text = $submission_text;

        return $this;
    }

    public function getSubmittedAt(): ?\DateTime
    {
        return $this->submitted_at;
    }

    public function setSubmittedAt(?\DateTime $submitted_at): static
    {
        $this->submitted_at = $submitted_at;

        return $this;
    }

    public function isLate(): ?bool
    {
        return $this->is_late;
    }

    public function setIsLate(?bool $is_late): static
    {
        $this->is_late = $is_late;

        return $this;
    }

    public function getReviewedAt(): ?\DateTime
    {
        return $this->reviewed_at;
    }

    public function setReviewedAt(?\DateTime $reviewed_at): static
    {
        $this->reviewed_at = $reviewed_at;

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
