<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ForumPostAttachmentRepository;

#[ORM\Entity(repositoryClass: ForumPostAttachmentRepository::class)]
#[ORM\Table(name: 'forum_post_attachments')]
class ForumPostAttachment
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

    #[ORM\ManyToOne(targetEntity: ForumPost::class, inversedBy: 'forumPostAttachments')]
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

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $file_name = null;

    public function getFile_name(): ?string
    {
        return $this->file_name;
    }

    public function setFile_name(?string $file_name): self
    {
        $this->file_name = $file_name;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $file_path = null;

    public function getFile_path(): ?string
    {
        return $this->file_path;
    }

    public function setFile_path(string $file_path): self
    {
        $this->file_path = $file_path;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $file_type = null;

    public function getFile_type(): ?string
    {
        return $this->file_type;
    }

    public function setFile_type(?string $file_type): self
    {
        $this->file_type = $file_type;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $uploaded_at = null;

    public function getUploaded_at(): ?\DateTimeInterface
    {
        return $this->uploaded_at;
    }

    public function setUploaded_at(?\DateTimeInterface $uploaded_at): self
    {
        $this->uploaded_at = $uploaded_at;
        return $this;
    }

    public function getFileName(): ?string
    {
        return $this->file_name;
    }

    public function setFileName(?string $file_name): static
    {
        $this->file_name = $file_name;

        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->file_path;
    }

    public function setFilePath(string $file_path): static
    {
        $this->file_path = $file_path;

        return $this;
    }

    public function getFileType(): ?string
    {
        return $this->file_type;
    }

    public function setFileType(?string $file_type): static
    {
        $this->file_type = $file_type;

        return $this;
    }

    public function getUploadedAt(): ?\DateTime
    {
        return $this->uploaded_at;
    }

    public function setUploadedAt(?\DateTime $uploaded_at): static
    {
        $this->uploaded_at = $uploaded_at;

        return $this;
    }

}
