<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ForumReviewRepository;

#[ORM\Entity(repositoryClass: ForumReviewRepository::class)]
#[ORM\Table(name: 'forum_reviews')]
class ForumReview
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

    #[ORM\OneToOne(targetEntity: ForumPost::class, inversedBy: 'forumReview')]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'id', unique: true)]
    private ?ForumPost $forumPost = null;

    public function getForumPost(): ?ForumPost
    {
        return $this->forumPost;
    }

    public function setForumPost(?ForumPost $forumPost): self
    {
        $this->forumPost = $forumPost;
        return $this;
    }

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'forumReview')]
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

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $rating = null;

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(int $rating): self
    {
        $this->rating = $rating;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $review_text = null;

    public function getReview_text(): ?string
    {
        return $this->review_text;
    }

    public function setReview_text(?string $review_text): self
    {
        $this->review_text = $review_text;
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

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $sync_uuid = null;

    public function getSync_uuid(): ?string
    {
        return $this->sync_uuid;
    }

    public function setSync_uuid(?string $sync_uuid): self
    {
        $this->sync_uuid = $sync_uuid;
        return $this;
    }

    public function getReviewText(): ?string
    {
        return $this->review_text;
    }

    public function setReviewText(?string $review_text): static
    {
        $this->review_text = $review_text;

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

    public function getSyncUuid(): ?string
    {
        return $this->sync_uuid;
    }

    public function setSyncUuid(?string $sync_uuid): static
    {
        $this->sync_uuid = $sync_uuid;

        return $this;
    }

}
