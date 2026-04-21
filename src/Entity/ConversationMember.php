<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ConversationMemberRepository;

#[ORM\Entity(repositoryClass: ConversationMemberRepository::class)]
#[ORM\Table(name: 'conversation_members')]
class ConversationMember
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

    #[ORM\OneToOne(targetEntity: Conversation::class, inversedBy: 'conversationMember')]
    #[ORM\JoinColumn(name: 'conversation_id', referencedColumnName: 'id', unique: true)]
    private ?Conversation $conversation = null;

    public function getConversation(): ?Conversation
    {
        return $this->conversation;
    }

    public function setConversation(?Conversation $conversation): self
    {
        $this->conversation = $conversation;
        return $this;
    }

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'conversationMember')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', unique: true)]
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
    private ?string $role = null;

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): self
    {
        $this->role = $role;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $joined_at = null;

    public function getJoined_at(): ?\DateTimeInterface
    {
        return $this->joined_at;
    }

    public function setJoined_at(?\DateTimeInterface $joined_at): self
    {
        $this->joined_at = $joined_at;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $left_at = null;

    public function getLeft_at(): ?\DateTimeInterface
    {
        return $this->left_at;
    }

    public function setLeft_at(?\DateTimeInterface $left_at): self
    {
        $this->left_at = $left_at;
        return $this;
    }

    public function getJoinedAt(): ?\DateTime
    {
        return $this->joined_at;
    }

    public function setJoinedAt(?\DateTime $joined_at): static
    {
        $this->joined_at = $joined_at;

        return $this;
    }

    public function getLeftAt(): ?\DateTime
    {
        return $this->left_at;
    }

    public function setLeftAt(?\DateTime $left_at): static
    {
        $this->left_at = $left_at;

        return $this;
    }

}
