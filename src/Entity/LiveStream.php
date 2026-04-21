<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\LiveStreamRepository;

#[ORM\Entity(repositoryClass: LiveStreamRepository::class)]
#[ORM\Table(name: 'live_streams')]
class LiveStream
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

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $teacher_id = null;

    public function getTeacher_id(): ?int
    {
        return $this->teacher_id;
    }

    public function setTeacher_id(int $teacher_id): self
    {
        $this->teacher_id = $teacher_id;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $teacher_name = null;

    public function getTeacher_name(): ?string
    {
        return $this->teacher_name;
    }

    public function setTeacher_name(?string $teacher_name): self
    {
        $this->teacher_name = $teacher_name;
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
    private ?\DateTimeInterface $start_time = null;

    public function getStart_time(): ?\DateTimeInterface
    {
        return $this->start_time;
    }

    public function setStart_time(?\DateTimeInterface $start_time): self
    {
        $this->start_time = $start_time;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $end_time = null;

    public function getEnd_time(): ?\DateTimeInterface
    {
        return $this->end_time;
    }

    public function setEnd_time(?\DateTimeInterface $end_time): self
    {
        $this->end_time = $end_time;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $viewer_count = null;

    public function getViewer_count(): ?int
    {
        return $this->viewer_count;
    }

    public function setViewer_count(?int $viewer_count): self
    {
        $this->viewer_count = $viewer_count;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $max_viewers = null;

    public function getMax_viewers(): ?int
    {
        return $this->max_viewers;
    }

    public function setMax_viewers(?int $max_viewers): self
    {
        $this->max_viewers = $max_viewers;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $stream_url = null;

    public function getStream_url(): ?string
    {
        return $this->stream_url;
    }

    public function setStream_url(?string $stream_url): self
    {
        $this->stream_url = $stream_url;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $stream_key = null;

    public function getStream_key(): ?string
    {
        return $this->stream_key;
    }

    public function setStream_key(?string $stream_key): self
    {
        $this->stream_key = $stream_key;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $thumbnail_url = null;

    public function getThumbnail_url(): ?string
    {
        return $this->thumbnail_url;
    }

    public function setThumbnail_url(?string $thumbnail_url): self
    {
        $this->thumbnail_url = $thumbnail_url;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $duration = null;

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): self
    {
        $this->duration = $duration;
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

    public function getTeacherId(): ?int
    {
        return $this->teacher_id;
    }

    public function setTeacherId(int $teacher_id): static
    {
        $this->teacher_id = $teacher_id;

        return $this;
    }

    public function getTeacherName(): ?string
    {
        return $this->teacher_name;
    }

    public function setTeacherName(?string $teacher_name): static
    {
        $this->teacher_name = $teacher_name;

        return $this;
    }

    public function getStartTime(): ?\DateTime
    {
        return $this->start_time;
    }

    public function setStartTime(?\DateTime $start_time): static
    {
        $this->start_time = $start_time;

        return $this;
    }

    public function getEndTime(): ?\DateTime
    {
        return $this->end_time;
    }

    public function setEndTime(?\DateTime $end_time): static
    {
        $this->end_time = $end_time;

        return $this;
    }

    public function getViewerCount(): ?int
    {
        return $this->viewer_count;
    }

    public function setViewerCount(?int $viewer_count): static
    {
        $this->viewer_count = $viewer_count;

        return $this;
    }

    public function getMaxViewers(): ?int
    {
        return $this->max_viewers;
    }

    public function setMaxViewers(?int $max_viewers): static
    {
        $this->max_viewers = $max_viewers;

        return $this;
    }

    public function getStreamUrl(): ?string
    {
        return $this->stream_url;
    }

    public function setStreamUrl(?string $stream_url): static
    {
        $this->stream_url = $stream_url;

        return $this;
    }

    public function getStreamKey(): ?string
    {
        return $this->stream_key;
    }

    public function setStreamKey(?string $stream_key): static
    {
        $this->stream_key = $stream_key;

        return $this;
    }

    public function getThumbnailUrl(): ?string
    {
        return $this->thumbnail_url;
    }

    public function setThumbnailUrl(?string $thumbnail_url): static
    {
        $this->thumbnail_url = $thumbnail_url;

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
