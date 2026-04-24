<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ForumCommentRepository;

#[ORM\Entity(repositoryClass: ForumCommentRepository::class)]
#[ORM\Table(name: 'forum_comments')]
class ForumComment
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

    #[ORM\ManyToOne(targetEntity: ForumPost::class, inversedBy: 'forumComments')]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'id')]
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

    #[ORM\ManyToOne(targetEntity: ForumComment::class, inversedBy: 'forumComments')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id')]
    private ?ForumComment $forumComment = null;

    public function getForumComment(): ?ForumComment
    {
        return $this->forumComment;
    }

    public function setForumComment(?ForumComment $forumComment): self
    {
        $this->forumComment = $forumComment;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'forumComments')]
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

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $emoji = null;

    public function getEmoji(): ?string
    {
        return $this->emoji;
    }

    public function setEmoji(?string $emoji): self
    {
        $this->emoji = $emoji;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: ForumComment::class, mappedBy: 'forumComment')]
    private Collection $forumComments;

    public function __construct()
    {
        $this->forumComments = new ArrayCollection();
    }

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

    public function __toString(): string
    {
        $content = trim((string) $this->content);

        if ($content !== '') {
            $short = mb_substr($content, 0, 42);

            return mb_strlen($content) > 42 ? $short . '...' : $short;
        }

        return sprintf('Comment #%d', (int) ($this->id ?? 0));
    }

}
