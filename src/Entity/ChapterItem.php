<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ChapterItemRepository;

#[ORM\Entity(repositoryClass: ChapterItemRepository::class)]
#[ORM\Table(name: 'chapter_items')]
class ChapterItem
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

    #[ORM\ManyToOne(targetEntity: Chapter::class, inversedBy: 'chapterItems')]
    #[ORM\JoinColumn(name: 'chapter_id', referencedColumnName: 'id')]
    private ?Chapter $chapter = null;

    public function getChapter(): ?Chapter
    {
        return $this->chapter;
    }

    public function setChapter(?Chapter $chapter): self
    {
        $this->chapter = $chapter;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $type = null;

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $sort_order = null;

    public function getSort_order(): ?int
    {
        return $this->sort_order;
    }

    public function setSort_order(?int $sort_order): self
    {
        $this->sort_order = $sort_order;
        return $this;
    }

    #[ORM\OneToOne(targetEntity: ChapterContent::class, mappedBy: 'chapterItem')]
    private ?ChapterContent $chapterContent = null;

    public function getChapterContent(): ?ChapterContent
    {
        return $this->chapterContent;
    }

    public function setChapterContent(?ChapterContent $chapterContent): self
    {
        $this->chapterContent = $chapterContent;
        return $this;
    }

    #[ORM\OneToOne(targetEntity: ChapterFile::class, mappedBy: 'chapterItem')]
    private ?ChapterFile $chapterFile = null;

    public function getChapterFile(): ?ChapterFile
    {
        return $this->chapterFile;
    }

    public function setChapterFile(?ChapterFile $chapterFile): self
    {
        $this->chapterFile = $chapterFile;
        return $this;
    }

    public function getSortOrder(): ?int
    {
        return $this->sort_order;
    }

    public function setSortOrder(?int $sort_order): static
    {
        $this->sort_order = $sort_order;

        return $this;
    }

}
