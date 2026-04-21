<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\AnnouncementRepository;

#[ORM\Entity(repositoryClass: AnnouncementRepository::class)]
#[ORM\Table(name: 'announcements')]
class Announcement
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

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'announcements')]
    #[ORM\JoinColumn(name: 'posted_by', referencedColumnName: 'id')]
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

    #[ORM\Column(type: 'text', nullable: false)]
    private ?string $content = null;

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $target_type = null;

    public function getTarget_type(): ?string
    {
        return $this->target_type;
    }

    public function setTarget_type(?string $target_type): self
    {
        $this->target_type = $target_type;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $target_id = null;

    public function getTarget_id(): ?int
    {
        return $this->target_id;
    }

    public function setTarget_id(?int $target_id): self
    {
        $this->target_id = $target_id;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $priority = null;

    public function getPriority(): ?string
    {
        return $this->priority;
    }

    public function setPriority(?string $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $is_pinned = null;

    public function is_pinned(): ?bool
    {
        return $this->is_pinned;
    }

    public function setIs_pinned(?bool $is_pinned): self
    {
        $this->is_pinned = $is_pinned;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $publish_at = null;

    public function getPublish_at(): ?\DateTimeInterface
    {
        return $this->publish_at;
    }

    public function setPublish_at(?\DateTimeInterface $publish_at): self
    {
        $this->publish_at = $publish_at;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $expire_at = null;

    public function getExpire_at(): ?\DateTimeInterface
    {
        return $this->expire_at;
    }

    public function setExpire_at(?\DateTimeInterface $expire_at): self
    {
        $this->expire_at = $expire_at;
        return $this;
    }

    public function getTargetType(): ?string
    {
        return $this->target_type;
    }

    public function setTargetType(?string $target_type): static
    {
        $this->target_type = $target_type;

        return $this;
    }

    public function getTargetId(): ?int
    {
        return $this->target_id;
    }

    public function setTargetId(?int $target_id): static
    {
        $this->target_id = $target_id;

        return $this;
    }

    public function isPinned(): ?bool
    {
        return $this->is_pinned;
    }

    public function setIsPinned(?bool $is_pinned): static
    {
        $this->is_pinned = $is_pinned;

        return $this;
    }

    public function getPublishAt(): ?\DateTime
    {
        return $this->publish_at;
    }

    public function setPublishAt(?\DateTime $publish_at): static
    {
        $this->publish_at = $publish_at;

        return $this;
    }

    public function getExpireAt(): ?\DateTime
    {
        return $this->expire_at;
    }

    public function setExpireAt(?\DateTime $expire_at): static
    {
        $this->expire_at = $expire_at;

        return $this;
    }

}
