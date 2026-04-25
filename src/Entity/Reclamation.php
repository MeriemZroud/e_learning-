<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use DateTime;

use App\Repository\ReclamationRepository;

#[ORM\Entity(repositoryClass: ReclamationRepository::class)]
#[ORM\Table(name: 'reclamations')]
class Reclamation
{
    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';
    public const STATUS_RESOLVED = 'RESOLVED';
    public const STATUS_REJECTED = 'REJECTED';
    public const PRIORITY_URGENT = 'URGENT';
    public const PRIORITY_NORMAL = 'NORMAL';
    public const PRIORITY_LOW = 'LOW';

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

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'reclamations')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
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

    #[ORM\Column(type: 'text', nullable: false)]
    private ?string $message = null;

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $status = null;

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        if ($status === null || $status === '') {
            $this->status = null;

            return $this;
        }

        $normalized = strtoupper(trim($status));
        $aliases = [
            'IN_PROGRESS' => self::STATUS_IN_PROGRESS,
            'PENDING' => self::STATUS_IN_PROGRESS,
            'RESOLVED' => self::STATUS_RESOLVED,
            'REJECTED' => self::STATUS_REJECTED,
        ];

        $this->status = $aliases[$normalized] ?? self::STATUS_IN_PROGRESS;

        return $this;
    }

    #[ORM\Column(type: 'string', length: 20, options: ['default' => 'NORMAL'])]
    private string $priority = self::PRIORITY_NORMAL;

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function setPriority(?string $priority): self
    {
        if ($priority === null || $priority === '') {
            $this->priority = self::PRIORITY_NORMAL;

            return $this;
        }

        $normalized = strtoupper(trim($priority));
        $aliases = [
            'URGENT' => self::PRIORITY_URGENT,
            'HIGH' => self::PRIORITY_URGENT,
            'NORMAL' => self::PRIORITY_NORMAL,
            'MEDIUM' => self::PRIORITY_NORMAL,
            'LOW' => self::PRIORITY_LOW,
            'FAIBLE' => self::PRIORITY_LOW,
        ];

        $this->priority = $aliases[$normalized] ?? self::PRIORITY_NORMAL;

        return $this;
    }

    public function getPriorityLabel(): string
    {
        return match ($this->priority) {
            self::PRIORITY_URGENT => 'Urgent',
            self::PRIORITY_LOW => 'Faible',
            default => 'Normal',
        };
    }

    #[ORM\Column(type: 'integer', options: ['default' => 50])]
    private int $priority_score = 50;

    public function getPriorityScore(): int
    {
        return $this->priority_score;
    }

    public function setPriorityScore(?int $priorityScore): self
    {
        $this->priority_score = max(0, min(100, (int) ($priorityScore ?? 50)));

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

    public function getCreatedAt(): ?\DateTime
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTime $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

}