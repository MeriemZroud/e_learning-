<?php

namespace App\Entity;

use App\Repository\CourseQuizSubmissionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CourseQuizSubmissionRepository::class)]
#[ORM\Table(name: 'course_quiz_submissions')]
class CourseQuizSubmission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CourseQuiz::class)]
    #[ORM\JoinColumn(name: 'quiz_id', referencedColumnName: 'id', nullable: false)]
    private ?CourseQuiz $quiz = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'student_id', referencedColumnName: 'id', nullable: false)]
    private ?User $student = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $answers_json = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $score = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $submitted_at = null;

    #[ORM\Column(type: 'string', length: 40, nullable: true)]
    private ?string $status = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuiz(): ?CourseQuiz
    {
        return $this->quiz;
    }

    public function setQuiz(?CourseQuiz $quiz): static
    {
        $this->quiz = $quiz;

        return $this;
    }

    public function getStudent(): ?User
    {
        return $this->student;
    }

    public function setStudent(?User $student): static
    {
        $this->student = $student;

        return $this;
    }

    public function getAnswersJson(): ?string
    {
        return $this->answers_json;
    }

    public function setAnswersJson(?string $answers_json): static
    {
        $this->answers_json = $answers_json;

        return $this;
    }

    public function getScore(): ?float
    {
        return $this->score;
    }

    public function setScore(?float $score): static
    {
        $this->score = $score;

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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }
}
