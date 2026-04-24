<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;


#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[UniqueEntity(fields: ['email'], message: 'This email is already used by another account.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Role::class, inversedBy: 'users')]
    #[ORM\JoinColumn(name: 'role_id', referencedColumnName: 'id')]
    private ?Role $role = null;

    #[ORM\Column(type: 'string')]
    private ?string $email = null;

    #[ORM\Column(type: 'string')]
    private ?string $password_hash = null;

    private ?string $plainPassword = null;

    #[ORM\Column(type: 'string')]
    private ?string $first_name = null;

    #[ORM\Column(type: 'string')]
    private ?string $last_name = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $date_of_birth = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $gender = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $employee_id = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $student_id = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $is_active = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $last_login_at = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private ?string $reset_password_token = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $reset_password_expires_at = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $profile_image = null;

    // ================= RELATIONS =================

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Announcement::class)]
    private Collection $announcements;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ChapterContent::class)]
    private Collection $chapterContents;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ChapterFile::class)]
    private Collection $chapterFiles;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: ChapterProgress::class)]
    private ?ChapterProgress $chapterProgress = null;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: ClassEnrollment::class)]
    private ?ClassEnrollment $classEnrollment = null;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: ConversationMember::class)]
    private ?ConversationMember $conversationMember = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Conversation::class)]
    private Collection $conversations;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ForumComment::class)]
    private Collection $forumComments;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ForumPost::class)]
    private Collection $forumPosts;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: ForumReview::class)]
    private ?ForumReview $forumReview = null;

    #[ORM\ManyToMany(targetEntity: Message::class, mappedBy: 'users')]
    private Collection $readMessages;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Notification::class)]
    private Collection $notifications;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Reclamation::class)]
    private Collection $reclamations;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: SubjectSection::class)]
    private Collection $subjectSections;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: SubmissionFile::class)]
    private Collection $submissionFiles;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Submission::class)]
    private Collection $submissions;

    public function __construct()
    {
        $this->announcements = new ArrayCollection();
        $this->chapterContents = new ArrayCollection();
        $this->chapterFiles = new ArrayCollection();
        $this->conversations = new ArrayCollection();
        $this->forumComments = new ArrayCollection();
        $this->forumPosts = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->readMessages = new ArrayCollection();
        $this->reclamations = new ArrayCollection();
        $this->subjectSections = new ArrayCollection();
        $this->submissionFiles = new ArrayCollection();
        $this->submissions = new ArrayCollection();
    }

    // ================= GETTERS / SETTERS =================

    public function getId(): ?int { return $this->id; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }

    public function getPassword_hash(): ?string { return $this->password_hash; }
    public function setPassword_hash(string $password_hash): self { $this->password_hash = $password_hash; return $this; }

    public function getFirst_name(): ?string { return $this->first_name; }
    public function setFirst_name(string $first_name): self { $this->first_name = $first_name; return $this; }

    public function getLast_name(): ?string { return $this->last_name; }
    public function setLast_name(string $last_name): self { $this->last_name = $last_name; return $this; }

    public function getSubmissions(): Collection { return $this->submissions; }

    public function addSubmission(Submission $submission): self {
        if (!$this->submissions->contains($submission)) {
            $this->submissions->add($submission);
            $submission->setUser($this);
        }
        return $this;
    }

    public function removeSubmission(Submission $submission): self {
        if ($this->submissions->removeElement($submission)) {
            if ($submission->getUser() === $this) {
                $submission->setUser(null);
            }
        }
        return $this;
    }

    public function getPasswordHash(): ?string
    {
        return $this->password_hash;
    }

    public function setPasswordHash(string $password_hash): static
    {
        $this->password_hash = $password_hash;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): static
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    public function setFirstName(string $first_name): static
    {
        $this->first_name = $first_name;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    public function setLastName(string $last_name): static
    {
        $this->last_name = $last_name;

        return $this;
    }

    public function getDateOfBirth(): ?\DateTime
    {
        return $this->date_of_birth;
    }

    public function setDateOfBirth(?\DateTime $date_of_birth): static
    {
        $this->date_of_birth = $date_of_birth;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): static
    {
        $this->gender = $gender;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getEmployeeId(): ?string
    {
        return $this->employee_id;
    }

    public function setEmployeeId(?string $employee_id): static
    {
        $this->employee_id = $employee_id;

        return $this;
    }

    public function getStudentId(): ?string
    {
        return $this->student_id;
    }

    public function setStudentId(?string $student_id): static
    {
        $this->student_id = $student_id;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->is_active;
    }

    public function setIsActive(?bool $is_active): static
    {
        $this->is_active = $is_active;

        return $this;
    }

    public function getLastLoginAt(): ?\DateTime
    {
        return $this->last_login_at;
    }

    public function setLastLoginAt(?\DateTime $last_login_at): static
    {
        $this->last_login_at = $last_login_at;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->created_at;
    }

    public function getCreatedAtString(): string
    {
        return $this->created_at instanceof \DateTimeInterface
            ? $this->created_at->format('Y-m-d H:i:s')
            : '';
    }

    public function setCreatedAt(?\DateTime $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getResetPasswordToken(): ?string
    {
        return $this->reset_password_token;
    }

    public function setResetPasswordToken(?string $reset_password_token): static
    {
        $this->reset_password_token = $reset_password_token;

        return $this;
    }

    public function getResetPasswordExpiresAt(): ?\DateTimeInterface
    {
        return $this->reset_password_expires_at;
    }

    public function setResetPasswordExpiresAt(?\DateTimeInterface $reset_password_expires_at): static
    {
        $this->reset_password_expires_at = $reset_password_expires_at;

        return $this;
    }

    public function getProfileImage(): ?string
    {
        return $this->profile_image;
    }

    public function setProfileImage(?string $profile_image): static
    {
        $this->profile_image = $profile_image;

        return $this;
    }

    public function getRole(): ?Role
    {
        return $this->role;
    }

    public function setRole(?Role $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getRoleLabel(): string
    {
        if (!$this->role instanceof Role) {
            return 'No role';
        }

        $name = trim((string) $this->role->getName());
        if ($name !== '') {
            return $name;
        }

        return (string) ($this->role->getRoleCategory() ?? 'No role');
    }

    public function getRoles(): array
    {
        if (!$this->role instanceof Role) {
            return ['ROLE_USER'];
        }

        $category = $this->role->getRoleCategory();
        if (!$category) {
            return ['ROLE_USER'];
        }

        if (str_starts_with(strtoupper($category), 'ROLE_')) {
            return [strtoupper($category)];
        }

        return ['ROLE_' . strtoupper($category)];
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    public function __toString(): string
    {
        $fullName = trim(sprintf('%s %s', (string) $this->first_name, (string) $this->last_name));
        if ($fullName !== '') {
            return $fullName;
        }

        if ($this->email !== null && $this->email !== '') {
            return $this->email;
        }

        return sprintf('User #%d', (int) ($this->id ?? 0));
    }

    public function getPassword(): ?string
    {
        return $this->password_hash;
    }

    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    /**
     * @return Collection<int, Announcement>
     */
    public function getAnnouncements(): Collection
    {
        return $this->announcements;
    }

    public function addAnnouncement(Announcement $announcement): static
    {
        if (!$this->announcements->contains($announcement)) {
            $this->announcements->add($announcement);
            $announcement->setUser($this);
        }

        return $this;
    }

    public function removeAnnouncement(Announcement $announcement): static
    {
        if ($this->announcements->removeElement($announcement)) {
            // set the owning side to null (unless already changed)
            if ($announcement->getUser() === $this) {
                $announcement->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ChapterContent>
     */
    public function getChapterContents(): Collection
    {
        return $this->chapterContents;
    }

    public function addChapterContent(ChapterContent $chapterContent): static
    {
        if (!$this->chapterContents->contains($chapterContent)) {
            $this->chapterContents->add($chapterContent);
            $chapterContent->setUser($this);
        }

        return $this;
    }

    public function removeChapterContent(ChapterContent $chapterContent): static
    {
        if ($this->chapterContents->removeElement($chapterContent)) {
            // set the owning side to null (unless already changed)
            if ($chapterContent->getUser() === $this) {
                $chapterContent->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ChapterFile>
     */
    public function getChapterFiles(): Collection
    {
        return $this->chapterFiles;
    }

    public function addChapterFile(ChapterFile $chapterFile): static
    {
        if (!$this->chapterFiles->contains($chapterFile)) {
            $this->chapterFiles->add($chapterFile);
            $chapterFile->setUser($this);
        }

        return $this;
    }

    public function removeChapterFile(ChapterFile $chapterFile): static
    {
        if ($this->chapterFiles->removeElement($chapterFile)) {
            // set the owning side to null (unless already changed)
            if ($chapterFile->getUser() === $this) {
                $chapterFile->setUser(null);
            }
        }

        return $this;
    }

    public function getChapterProgress(): ?ChapterProgress
    {
        return $this->chapterProgress;
    }

    public function setChapterProgress(?ChapterProgress $chapterProgress): static
    {
        // unset the owning side of the relation if necessary
        if ($chapterProgress === null && $this->chapterProgress !== null) {
            $this->chapterProgress->setUser(null);
        }

        // set the owning side of the relation if necessary
        if ($chapterProgress !== null && $chapterProgress->getUser() !== $this) {
            $chapterProgress->setUser($this);
        }

        $this->chapterProgress = $chapterProgress;

        return $this;
    }

    public function getClassEnrollment(): ?ClassEnrollment
    {
        return $this->classEnrollment;
    }

    public function setClassEnrollment(?ClassEnrollment $classEnrollment): static
    {
        // unset the owning side of the relation if necessary
        if ($classEnrollment === null && $this->classEnrollment !== null) {
            $this->classEnrollment->setUser(null);
        }

        // set the owning side of the relation if necessary
        if ($classEnrollment !== null && $classEnrollment->getUser() !== $this) {
            $classEnrollment->setUser($this);
        }

        $this->classEnrollment = $classEnrollment;

        return $this;
    }

    public function getConversationMember(): ?ConversationMember
    {
        return $this->conversationMember;
    }

    public function setConversationMember(?ConversationMember $conversationMember): static
    {
        // unset the owning side of the relation if necessary
        if ($conversationMember === null && $this->conversationMember !== null) {
            $this->conversationMember->setUser(null);
        }

        // set the owning side of the relation if necessary
        if ($conversationMember !== null && $conversationMember->getUser() !== $this) {
            $conversationMember->setUser($this);
        }

        $this->conversationMember = $conversationMember;

        return $this;
    }

    /**
     * @return Collection<int, Conversation>
     */
    public function getConversations(): Collection
    {
        return $this->conversations;
    }

    public function addConversation(Conversation $conversation): static
    {
        if (!$this->conversations->contains($conversation)) {
            $this->conversations->add($conversation);
            $conversation->setUser($this);
        }

        return $this;
    }

    public function removeConversation(Conversation $conversation): static
    {
        if ($this->conversations->removeElement($conversation)) {
            // set the owning side to null (unless already changed)
            if ($conversation->getUser() === $this) {
                $conversation->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ForumComment>
     */
    public function getForumComments(): Collection
    {
        return $this->forumComments;
    }

    public function addForumComment(ForumComment $forumComment): static
    {
        if (!$this->forumComments->contains($forumComment)) {
            $this->forumComments->add($forumComment);
            $forumComment->setUser($this);
        }

        return $this;
    }

    public function removeForumComment(ForumComment $forumComment): static
    {
        if ($this->forumComments->removeElement($forumComment)) {
            // set the owning side to null (unless already changed)
            if ($forumComment->getUser() === $this) {
                $forumComment->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ForumPost>
     */
    public function getForumPosts(): Collection
    {
        return $this->forumPosts;
    }

    public function addForumPost(ForumPost $forumPost): static
    {
        if (!$this->forumPosts->contains($forumPost)) {
            $this->forumPosts->add($forumPost);
            $forumPost->setUser($this);
        }

        return $this;
    }

    public function removeForumPost(ForumPost $forumPost): static
    {
        if ($this->forumPosts->removeElement($forumPost)) {
            // set the owning side to null (unless already changed)
            if ($forumPost->getUser() === $this) {
                $forumPost->setUser(null);
            }
        }

        return $this;
    }

    public function getForumReview(): ?ForumReview
    {
        return $this->forumReview;
    }

    public function setForumReview(?ForumReview $forumReview): static
    {
        // unset the owning side of the relation if necessary
        if ($forumReview === null && $this->forumReview !== null) {
            $this->forumReview->setUser(null);
        }

        // set the owning side of the relation if necessary
        if ($forumReview !== null && $forumReview->getUser() !== $this) {
            $forumReview->setUser($this);
        }

        $this->forumReview = $forumReview;

        return $this;
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setUser($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getUser() === $this) {
                $notification->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Reclamation>
     */
    public function getReclamations(): Collection
    {
        return $this->reclamations;
    }

    public function addReclamation(Reclamation $reclamation): static
    {
        if (!$this->reclamations->contains($reclamation)) {
            $this->reclamations->add($reclamation);
            $reclamation->setUser($this);
        }

        return $this;
    }

    public function removeReclamation(Reclamation $reclamation): static
    {
        if ($this->reclamations->removeElement($reclamation)) {
            // set the owning side to null (unless already changed)
            if ($reclamation->getUser() === $this) {
                $reclamation->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, SubjectSection>
     */
    public function getSubjectSections(): Collection
    {
        return $this->subjectSections;
    }

    public function addSubjectSection(SubjectSection $subjectSection): static
    {
        if (!$this->subjectSections->contains($subjectSection)) {
            $this->subjectSections->add($subjectSection);
            $subjectSection->setUser($this);
        }

        return $this;
    }

    public function removeSubjectSection(SubjectSection $subjectSection): static
    {
        if ($this->subjectSections->removeElement($subjectSection)) {
            // set the owning side to null (unless already changed)
            if ($subjectSection->getUser() === $this) {
                $subjectSection->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, SubmissionFile>
     */
    public function getSubmissionFiles(): Collection
    {
        return $this->submissionFiles;
    }

    public function addSubmissionFile(SubmissionFile $submissionFile): static
    {
        if (!$this->submissionFiles->contains($submissionFile)) {
            $this->submissionFiles->add($submissionFile);
            $submissionFile->setUser($this);
        }

        return $this;
    }

    public function removeSubmissionFile(SubmissionFile $submissionFile): static
    {
        if ($this->submissionFiles->removeElement($submissionFile)) {
            // set the owning side to null (unless already changed)
            if ($submissionFile->getUser() === $this) {
                $submissionFile->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getReadMessages(): Collection
    {
        return $this->readMessages;
    }

    public function addReadMessage(Message $readMessage): static
    {
        if (!$this->readMessages->contains($readMessage)) {
            $this->readMessages->add($readMessage);
            $readMessage->addUser($this);
        }

        return $this;
    }

    public function removeReadMessage(Message $readMessage): static
    {
        if ($this->readMessages->removeElement($readMessage)) {
            $readMessage->removeUser($this);
        }

        return $this;
    }
}