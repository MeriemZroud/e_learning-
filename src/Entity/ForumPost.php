<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ForumPostRepository;

#[ORM\Entity(repositoryClass: ForumPostRepository::class)]
#[ORM\Table(name: 'forum_posts')]
class ForumPost
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

    #[ORM\ManyToOne(targetEntity: Classe::class, inversedBy: 'forumPosts')]
    #[ORM\JoinColumn(name: 'class_id', referencedColumnName: 'id')]
    private ?Classe $classe = null;

    public function getClasse(): ?Classe
    {
        return $this->classe;
    }

    public function setClasse(?Classe $classe): self
    {
        $this->classe = $classe;
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

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'forumPosts')]
    #[ORM\JoinColumn(name: 'created_by', referencedColumnName: 'id')]
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

    #[ORM\OneToMany(targetEntity: ForumComment::class, mappedBy: 'forumPost')]
    private Collection $forumComments;

    /**
     * @return Collection<int, ForumComment>
     */
    public function getForumComments(): Collection
    {
        if (!$this->forumComments instanceof Collection) {
            $this->forumComments = new ArrayCollection();
        }
        return $this->forumComments;
    }

    public function addForumComment(ForumComment $forumComment): self
    {
        if (!$this->getForumComments()->contains($forumComment)) {
            $this->getForumComments()->add($forumComment);
        }
        return $this;
    }

    public function removeForumComment(ForumComment $forumComment): self
    {
        $this->getForumComments()->removeElement($forumComment);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: ForumPostAttachment::class, mappedBy: 'forumPost')]
    private Collection $forumPostAttachments;

    /**
     * @return Collection<int, ForumPostAttachment>
     */
    public function getForumPostAttachments(): Collection
    {
        if (!$this->forumPostAttachments instanceof Collection) {
            $this->forumPostAttachments = new ArrayCollection();
        }
        return $this->forumPostAttachments;
    }

    public function addForumPostAttachment(ForumPostAttachment $forumPostAttachment): self
    {
        if (!$this->getForumPostAttachments()->contains($forumPostAttachment)) {
            $this->getForumPostAttachments()->add($forumPostAttachment);
        }
        return $this;
    }

    public function removeForumPostAttachment(ForumPostAttachment $forumPostAttachment): self
    {
        $this->getForumPostAttachments()->removeElement($forumPostAttachment);
        return $this;
    }

    #[ORM\OneToOne(targetEntity: ForumReview::class, mappedBy: 'forumPost')]
    private ?ForumReview $forumReview = null;

    public function __construct()
    {
        $this->forumComments = new ArrayCollection();
        $this->forumPostAttachments = new ArrayCollection();
    }

    public function getForumReview(): ?ForumReview
    {
        return $this->forumReview;
    }

    public function setForumReview(?ForumReview $forumReview): self
    {
        $this->forumReview = $forumReview;
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
