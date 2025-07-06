<?php

declare(strict_types=1);

namespace App\Document;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Doctrine\Odm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Odm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Odm\Filter\BooleanFilter;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ODM\Document(collection: 'mailboxes')]
#[ODM\Index(keys: ['emailAccount.$id' => 'asc', 'name' => 'asc'])]
#[ODM\Index(keys: ['path' => 'asc'])]
#[ODM\Index(keys: ['isDefault' => 'desc'])]
#[ODM\Index(keys: ['lastAccess' => 'desc'])]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['mailbox:read', 'mailbox:list']]
        ),
        new Post(
            denormalizationContext: ['groups' => ['mailbox:write']],
            normalizationContext: ['groups' => ['mailbox:read']]
        ),
        new Get(
            normalizationContext: ['groups' => ['mailbox:read', 'mailbox:detail']]
        ),
        new Put(
            denormalizationContext: ['groups' => ['mailbox:write']],
            normalizationContext: ['groups' => ['mailbox:read']]
        ),
        new Patch(
            denormalizationContext: ['groups' => ['mailbox:write']],
            normalizationContext: ['groups' => ['mailbox:read']]
        ),
        new Delete()
    ],
    normalizationContext: ['groups' => ['mailbox:read']],
    denormalizationContext: ['groups' => ['mailbox:write']],
    paginationEnabled: true,
    paginationItemsPerPage: 100
)]
#[ApiFilter(SearchFilter::class, properties: ['name' => 'partial', 'path' => 'partial'])]
#[ApiFilter(BooleanFilter::class, properties: ['isDefault', 'isSelectable'])]
#[ApiFilter(OrderFilter::class, properties: ['name', 'totalMessages', 'unreadMessages', 'lastAccess'])]
class Mailbox
{
    #[ODM\Id]
    #[Groups(['mailbox:read'])]
    private ?string $id = null;

    #[ODM\ReferenceOne(targetDocument: EmailAccount::class, inversedBy: 'mailboxes')]
    #[Assert\NotNull(message: 'Email account is required.')]
    #[Groups(['mailbox:read', 'mailbox:write'])]
    private ?EmailAccount $emailAccount = null;

    #[ODM\Field(type: 'string')]
    #[Assert\NotBlank(message: 'Mailbox name is required.')]
    #[Assert\Length(
        min: 1,
        max: 255,
        minMessage: 'Mailbox name must be at least {{ limit }} character long.',
        maxMessage: 'Mailbox name cannot be longer than {{ limit }} characters.'
    )]
    #[Groups(['mailbox:read', 'mailbox:write'])]
    private ?string $name = null;

    #[ODM\Field(type: 'string')]
    #[Assert\NotBlank(message: 'Mailbox path is required.')]
    #[Assert\Length(
        min: 1,
        max: 500,
        minMessage: 'Mailbox path must be at least {{ limit }} character long.',
        maxMessage: 'Mailbox path cannot be longer than {{ limit }} characters.'
    )]
    #[Groups(['mailbox:read', 'mailbox:write'])]
    private ?string $path = null;

    #[ODM\Field(type: 'string', nullable: true)]
    #[Assert\Length(
        max: 500,
        maxMessage: 'Description cannot be longer than {{ limit }} characters.'
    )]
    #[Groups(['mailbox:read', 'mailbox:write'])]
    private ?string $description = null;

    #[ODM\Field(type: 'bool')]
    #[Groups(['mailbox:read', 'mailbox:write'])]
    private bool $isDefault = false;

    #[ODM\Field(type: 'bool')]
    #[Groups(['mailbox:read', 'mailbox:write'])]
    private bool $isSelectable = true;

    #[ODM\Field(type: 'bool')]
    #[Groups(['mailbox:read', 'mailbox:write'])]
    private bool $isSubscribed = true;

    #[ODM\Field(type: 'string', nullable: true)]
    #[Groups(['mailbox:read', 'mailbox:write'])]
    private ?string $parentPath = null;

    #[ODM\Field(type: 'collection')]
    #[Groups(['mailbox:read', 'mailbox:write'])]
    private array $attributes = [];

    #[ODM\Field(type: 'int')]
    #[Groups(['mailbox:read'])]
    private int $totalMessages = 0;

    #[ODM\Field(type: 'int')]
    #[Groups(['mailbox:read'])]
    private int $unreadMessages = 0;

    #[ODM\Field(type: 'int')]
    #[Groups(['mailbox:read'])]
    private int $recentMessages = 0;

    #[ODM\Field(type: 'int')]
    #[Groups(['mailbox:read'])]
    private int $totalSizeMb = 0;

    #[ODM\Field(type: 'int')]
    #[Groups(['mailbox:read', 'mailbox:write'])]
    private int $uidNext = 1;

    #[ODM\Field(type: 'int')]
    #[Groups(['mailbox:read'])]
    private int $uidValidity = 0;

    #[ODM\Field(type: 'collection')]
    #[Groups(['mailbox:read', 'mailbox:write'])]
    private array $flags = ['\\Seen', '\\Answered', '\\Flagged', '\\Deleted', '\\Draft'];

    #[ODM\Field(type: 'collection')]
    #[Groups(['mailbox:read', 'mailbox:write'])]
    private array $permanentFlags = ['\\*'];

    #[ODM\Field(type: 'hash')]
    #[Groups(['mailbox:read', 'mailbox:detail'])]
    private array $statistics = [
        'firstMessage' => null,
        'lastMessage' => null,
        'averageSize' => 0,
        'oldestUnread' => null
    ];

    #[ODM\Field(type: 'date', nullable: true)]
    #[Groups(['mailbox:read'])]
    private ?\DateTimeInterface $lastAccess = null;

    #[ODM\Field(type: 'date', nullable: true)]
    #[Groups(['mailbox:read'])]
    private ?\DateTimeInterface $lastMessageAt = null;

    #[ODM\Field(type: 'hash')]
    #[Groups(['mailbox:read'])]
    private array $nightwatchMetadata = [];

    #[ODM\Field(type: 'date')]
    #[Groups(['mailbox:read'])]
    private \DateTimeInterface $createdAt;

    #[ODM\Field(type: 'date')]
    #[Groups(['mailbox:read'])]
    private \DateTimeInterface $updatedAt;

    #[ODM\ReferenceMany(targetDocument: Message::class, mappedBy: 'mailbox')]
    #[Groups(['mailbox:detail'])]
    private Collection $messages;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->attributes = [];
        $this->flags = ['\\Seen', '\\Answered', '\\Flagged', '\\Deleted', '\\Draft'];
        $this->permanentFlags = ['\\*'];
        $this->nightwatchMetadata = [];
        $this->statistics = [
            'firstMessage' => null,
            'lastMessage' => null,
            'averageSize' => 0,
            'oldestUnread' => null
        ];
        $this->uidValidity = time(); // Set to current timestamp
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getEmailAccount(): ?EmailAccount
    {
        return $this->emailAccount;
    }

    public function setEmailAccount(?EmailAccount $emailAccount): static
    {
        $this->emailAccount = $emailAccount;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): static
    {
        $this->path = $path;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): static
    {
        $this->isDefault = $isDefault;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function isSelectable(): bool
    {
        return $this->isSelectable;
    }

    public function setIsSelectable(bool $isSelectable): static
    {
        $this->isSelectable = $isSelectable;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function isSubscribed(): bool
    {
        return $this->isSubscribed;
    }

    public function setIsSubscribed(bool $isSubscribed): static
    {
        $this->isSubscribed = $isSubscribed;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getParentPath(): ?string
    {
        return $this->parentPath;
    }

    public function setParentPath(?string $parentPath): static
    {
        $this->parentPath = $parentPath;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function setAttributes(array $attributes): static
    {
        $this->attributes = $attributes;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function setAttribute(string $key, mixed $value): static
    {
        $this->attributes[$key] = $value;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getTotalMessages(): int
    {
        return $this->totalMessages;
    }

    public function setTotalMessages(int $totalMessages): static
    {
        $this->totalMessages = max(0, $totalMessages);
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function incrementTotalMessages(): static
    {
        $this->totalMessages++;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function decrementTotalMessages(): static
    {
        $this->totalMessages = max(0, $this->totalMessages - 1);
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getUnreadMessages(): int
    {
        return $this->unreadMessages;
    }

    public function setUnreadMessages(int $unreadMessages): static
    {
        $this->unreadMessages = max(0, $unreadMessages);
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function incrementUnreadMessages(): static
    {
        $this->unreadMessages++;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function decrementUnreadMessages(): static
    {
        $this->unreadMessages = max(0, $this->unreadMessages - 1);
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getRecentMessages(): int
    {
        return $this->recentMessages;
    }

    public function setRecentMessages(int $recentMessages): static
    {
        $this->recentMessages = max(0, $recentMessages);
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getTotalSizeMb(): int
    {
        return $this->totalSizeMb;
    }

    public function setTotalSizeMb(int $totalSizeMb): static
    {
        $this->totalSizeMb = max(0, $totalSizeMb);
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getUidNext(): int
    {
        return $this->uidNext;
    }

    public function setUidNext(int $uidNext): static
    {
        $this->uidNext = $uidNext;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getNextUid(): int
    {
        return $this->uidNext++;
    }

    public function getUidValidity(): int
    {
        return $this->uidValidity;
    }

    public function setUidValidity(int $uidValidity): static
    {
        $this->uidValidity = $uidValidity;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getFlags(): array
    {
        return $this->flags;
    }

    public function setFlags(array $flags): static
    {
        $this->flags = array_values(array_unique($flags));
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function addFlag(string $flag): static
    {
        if (!in_array($flag, $this->flags)) {
            $this->flags[] = $flag;
            $this->updatedAt = new \DateTime();
        }
        return $this;
    }

    public function removeFlag(string $flag): static
    {
        $this->flags = array_values(array_filter($this->flags, fn($f) => $f !== $flag));
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getPermanentFlags(): array
    {
        return $this->permanentFlags;
    }

    public function setPermanentFlags(array $permanentFlags): static
    {
        $this->permanentFlags = array_values(array_unique($permanentFlags));
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getStatistics(): array
    {
        return $this->statistics;
    }

    public function setStatistics(array $statistics): static
    {
        $this->statistics = array_merge($this->statistics, $statistics);
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function updateStatistic(string $key, mixed $value): static
    {
        $this->statistics[$key] = $value;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getLastAccess(): ?\DateTimeInterface
    {
        return $this->lastAccess;
    }

    public function setLastAccess(?\DateTimeInterface $lastAccess): static
    {
        $this->lastAccess = $lastAccess;
        return $this;
    }

    public function updateLastAccess(): static
    {
        $this->lastAccess = new \DateTime();
        return $this;
    }

    public function getLastMessageAt(): ?\DateTimeInterface
    {
        return $this->lastMessageAt;
    }

    public function setLastMessageAt(?\DateTimeInterface $lastMessageAt): static
    {
        $this->lastMessageAt = $lastMessageAt;
        return $this;
    }

    public function getNightwatchMetadata(): array
    {
        return $this->nightwatchMetadata;
    }

    public function setNightwatchMetadata(array $nightwatchMetadata): static
    {
        $this->nightwatchMetadata = $nightwatchMetadata;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setMailbox($this);
            $this->incrementTotalMessages();
            $this->lastMessageAt = new \DateTime();
        }
        return $this;
    }

    public function removeMessage(Message $message): static
    {
        if ($this->messages->removeElement($message)) {
            if ($message->getMailbox() === $this) {
                $message->setMailbox(null);
            }
            $this->decrementTotalMessages();
        }
        return $this;
    }

    // Business Logic Methods
    public function isInbox(): bool
    {
        return strtolower($this->name) === 'inbox' || strtolower($this->path) === 'inbox';
    }

    public function isSent(): bool
    {
        return in_array(strtolower($this->name), ['sent', 'sent items']) || 
               in_array(strtolower($this->path), ['sent', 'sent items']);
    }

    public function isDrafts(): bool
    {
        return strtolower($this->name) === 'drafts' || strtolower($this->path) === 'drafts';
    }

    public function isTrash(): bool
    {
        return in_array(strtolower($this->name), ['trash', 'deleted items']) || 
               in_array(strtolower($this->path), ['trash', 'deleted items']);
    }

    public function isSpam(): bool
    {
        return in_array(strtolower($this->name), ['spam', 'junk']) || 
               in_array(strtolower($this->path), ['spam', 'junk']);
    }

    public function hasUnreadMessages(): bool
    {
        return $this->unreadMessages > 0;
    }

    public function getUnreadPercentage(): float
    {
        if ($this->totalMessages === 0) {
            return 0.0;
        }
        return ($this->unreadMessages / $this->totalMessages) * 100;
    }

    public function getAverageSizeKb(): float
    {
        if ($this->totalMessages === 0) {
            return 0.0;
        }
        return ($this->totalSizeMb * 1024) / $this->totalMessages;
    }

    public function __toString(): string
    {
        return $this->name ?? $this->path ?? '';
    }
}