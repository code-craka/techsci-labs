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
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ODM\Document(collection: 'email_accounts')]
#[ODM\Index(keys: ['email' => 'asc'], options: ['unique' => true])]
#[ODM\Index(keys: ['domain.$id' => 'asc'])]
#[ODM\Index(keys: ['isActive' => 'desc'])]
#[ODM\Index(keys: ['createdAt' => 'desc'])]
#[ODM\Index(keys: ['lastLoginAt' => 'desc'])]
#[UniqueEntity(fields: ['email'], message: 'This email address already exists.')]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['account:read', 'account:list']]
        ),
        new Post(
            denormalizationContext: ['groups' => ['account:write']],
            normalizationContext: ['groups' => ['account:read']]
        ),
        new Get(
            normalizationContext: ['groups' => ['account:read', 'account:detail']]
        ),
        new Put(
            denormalizationContext: ['groups' => ['account:write']],
            normalizationContext: ['groups' => ['account:read']]
        ),
        new Patch(
            denormalizationContext: ['groups' => ['account:write']],
            normalizationContext: ['groups' => ['account:read']]
        ),
        new Delete()
    ],
    normalizationContext: ['groups' => ['account:read']],
    denormalizationContext: ['groups' => ['account:write']],
    paginationEnabled: true,
    paginationItemsPerPage: 50
)]
#[ApiFilter(SearchFilter::class, properties: ['email' => 'partial', 'domain.domain' => 'partial'])]
#[ApiFilter(BooleanFilter::class, properties: ['isActive', 'isDeleted'])]
#[ApiFilter(OrderFilter::class, properties: ['email', 'createdAt', 'lastLoginAt', 'usedQuotaMb'])]
class EmailAccount implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ODM\Id]
    #[Groups(['account:read'])]
    private ?string $id = null;

    #[ODM\Field(type: 'string')]
    #[Assert\NotBlank(message: 'Email address is required.')]
    #[Assert\Email(message: 'Please enter a valid email address.')]
    #[Assert\Length(
        min: 3,
        max: 320,
        minMessage: 'Email must be at least {{ limit }} characters long.',
        maxMessage: 'Email cannot be longer than {{ limit }} characters.'
    )]
    #[Groups(['account:read', 'account:write'])]
    private ?string $email = null;

    #[ODM\Field(type: 'string')]
    #[Assert\NotBlank(message: 'Password is required.')]
    #[Assert\Length(
        min: 6,
        minMessage: 'Password must be at least {{ limit }} characters long.'
    )]
    #[Groups(['account:write'])]
    private ?string $password = null;

    #[ODM\ReferenceOne(targetDocument: Domain::class, inversedBy: 'emailAccounts')]
    #[Assert\NotNull(message: 'Domain is required.')]
    #[Groups(['account:read', 'account:write'])]
    private ?Domain $domain = null;

    #[ODM\Field(type: 'bool')]
    #[Groups(['account:read', 'account:write'])]
    private bool $isActive = true;

    #[ODM\Field(type: 'bool')]
    #[Groups(['account:read'])]
    private bool $isDeleted = false;

    #[ODM\Field(type: 'int')]
    #[Assert\PositiveOrZero(message: 'Quota must be zero or positive.')]
    #[Groups(['account:read', 'account:write'])]
    private int $quotaMb = 1024; // 1GB default

    #[ODM\Field(type: 'int')]
    #[Groups(['account:read'])]
    private int $usedQuotaMb = 0;

    #[ODM\Field(type: 'collection')]
    #[Groups(['account:read', 'account:write'])]
    private array $aliases = [];

    #[ODM\Field(type: 'collection')]
    #[Groups(['account:read', 'account:write'])]
    private array $tags = [];

    #[ODM\Field(type: 'hash')]
    #[Groups(['account:read', 'account:write'])]
    private array $settings = [
        'autoExpiry' => null,
        'forwardTo' => null,
        'autoReply' => null,
        'notificationsEnabled' => true,
        'webmailAccess' => true,
        'imapAccess' => true,
        'pop3Access' => true
    ];

    #[ODM\Field(type: 'hash')]
    #[Groups(['account:read', 'account:detail'])]
    private array $statistics = [
        'totalMessages' => 0,
        'unreadMessages' => 0,
        'totalSent' => 0,
        'totalReceived' => 0,
        'lastMessageAt' => null,
        'firstMessageAt' => null
    ];

    #[ODM\Field(type: 'collection')]
    #[Groups(['account:read'])]
    private array $roles = ['ROLE_USER'];

    #[ODM\Field(type: 'string', nullable: true)]
    #[Groups(['account:read', 'account:write'])]
    private ?string $apiKey = null;

    #[ODM\Field(type: 'date', nullable: true)]
    #[Groups(['account:read'])]
    private ?\DateTimeInterface $lastLoginAt = null;

    #[ODM\Field(type: 'date', nullable: true)]
    #[Groups(['account:read'])]
    private ?\DateTimeInterface $lastActivityAt = null;

    #[ODM\Field(type: 'date', nullable: true)]
    #[Groups(['account:read', 'account:write'])]
    private ?\DateTimeInterface $expiresAt = null;

    #[ODM\Field(type: 'string', nullable: true)]
    #[Groups(['account:read'])]
    private ?string $ipAddress = null;

    #[ODM\Field(type: 'string', nullable: true)]
    #[Groups(['account:read'])]
    private ?string $userAgent = null;

    #[ODM\Field(type: 'hash')]
    #[Groups(['account:read'])]
    private array $nightwatchMetadata = [];

    #[ODM\Field(type: 'date')]
    #[Groups(['account:read'])]
    private \DateTimeInterface $createdAt;

    #[ODM\Field(type: 'date')]
    #[Groups(['account:read'])]
    private \DateTimeInterface $updatedAt;

    #[ODM\ReferenceMany(targetDocument: Mailbox::class, mappedBy: 'emailAccount')]
    #[Groups(['account:detail'])]
    private Collection $mailboxes;

    #[ODM\ReferenceMany(targetDocument: Token::class, mappedBy: 'emailAccount')]
    #[Groups(['account:detail'])]
    private Collection $tokens;

    public function __construct()
    {
        $this->mailboxes = new ArrayCollection();
        $this->tokens = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->aliases = [];
        $this->tags = [];
        $this->roles = ['ROLE_USER'];
        $this->nightwatchMetadata = [];
        $this->settings = [
            'autoExpiry' => null,
            'forwardTo' => null,
            'autoReply' => null,
            'notificationsEnabled' => true,
            'webmailAccess' => true,
            'imapAccess' => true,
            'pop3Access' => true
        ];
        $this->statistics = [
            'totalMessages' => 0,
            'unreadMessages' => 0,
            'totalSent' => 0,
            'totalReceived' => 0,
            'lastMessageAt' => null,
            'firstMessageAt' => null
        ];
    }

    // UserInterface implementation
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function eraseCredentials(): void
    {
        // Clear any temporary sensitive data
    }

    // PasswordAuthenticatedUserInterface implementation
    public function getPassword(): ?string
    {
        return $this->password;
    }

    // Getters and Setters
    public function getId(): ?string
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = strtolower(trim($email));
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getDomain(): ?Domain
    {
        return $this->domain;
    }

    public function setDomain(?Domain $domain): static
    {
        $this->domain = $domain;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(bool $isDeleted): static
    {
        $this->isDeleted = $isDeleted;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getQuotaMb(): int
    {
        return $this->quotaMb;
    }

    public function setQuotaMb(int $quotaMb): static
    {
        $this->quotaMb = $quotaMb;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getUsedQuotaMb(): int
    {
        return $this->usedQuotaMb;
    }

    public function setUsedQuotaMb(int $usedQuotaMb): static
    {
        $this->usedQuotaMb = $usedQuotaMb;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getAliases(): array
    {
        return $this->aliases;
    }

    public function setAliases(array $aliases): static
    {
        $this->aliases = array_map('strtolower', array_map('trim', $aliases));
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function addAlias(string $alias): static
    {
        $alias = strtolower(trim($alias));
        if (!in_array($alias, $this->aliases)) {
            $this->aliases[] = $alias;
            $this->updatedAt = new \DateTime();
        }
        return $this;
    }

    public function removeAlias(string $alias): static
    {
        $alias = strtolower(trim($alias));
        $this->aliases = array_values(array_filter($this->aliases, fn($a) => $a !== $alias));
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function setTags(array $tags): static
    {
        $this->tags = array_values(array_unique($tags));
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function addTag(string $tag): static
    {
        if (!in_array($tag, $this->tags)) {
            $this->tags[] = $tag;
            $this->updatedAt = new \DateTime();
        }
        return $this;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function setSettings(array $settings): static
    {
        $this->settings = array_merge($this->settings, $settings);
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getSetting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }

    public function setSetting(string $key, mixed $value): static
    {
        $this->settings[$key] = $value;
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

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function setApiKey(?string $apiKey): static
    {
        $this->apiKey = $apiKey;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getLastLoginAt(): ?\DateTimeInterface
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeInterface $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;
        return $this;
    }

    public function getLastActivityAt(): ?\DateTimeInterface
    {
        return $this->lastActivityAt;
    }

    public function setLastActivityAt(?\DateTimeInterface $lastActivityAt): static
    {
        $this->lastActivityAt = $lastActivityAt;
        return $this;
    }

    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeInterface $expiresAt): static
    {
        $this->expiresAt = $expiresAt;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        $this->userAgent = $userAgent;
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
     * @return Collection<int, Mailbox>
     */
    public function getMailboxes(): Collection
    {
        return $this->mailboxes;
    }

    public function addMailbox(Mailbox $mailbox): static
    {
        if (!$this->mailboxes->contains($mailbox)) {
            $this->mailboxes->add($mailbox);
            $mailbox->setEmailAccount($this);
        }
        return $this;
    }

    public function removeMailbox(Mailbox $mailbox): static
    {
        if ($this->mailboxes->removeElement($mailbox)) {
            if ($mailbox->getEmailAccount() === $this) {
                $mailbox->setEmailAccount(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Token>
     */
    public function getTokens(): Collection
    {
        return $this->tokens;
    }

    public function addToken(Token $token): static
    {
        if (!$this->tokens->contains($token)) {
            $this->tokens->add($token);
            $token->setEmailAccount($this);
        }
        return $this;
    }

    // Business Logic Methods
    public function isExpired(): bool
    {
        return $this->expiresAt !== null && $this->expiresAt < new \DateTime();
    }

    public function hasQuotaExceeded(): bool
    {
        return $this->usedQuotaMb >= $this->quotaMb;
    }

    public function getRemainingQuotaMb(): int
    {
        return max(0, $this->quotaMb - $this->usedQuotaMb);
    }

    public function getQuotaUsagePercent(): float
    {
        if ($this->quotaMb === 0) {
            return 0.0;
        }
        return ($this->usedQuotaMb / $this->quotaMb) * 100;
    }

    public function canReceiveEmail(): bool
    {
        return $this->isActive && 
               !$this->isDeleted && 
               !$this->isExpired() && 
               !$this->hasQuotaExceeded();
    }

    public function generateApiKey(): string
    {
        $this->apiKey = bin2hex(random_bytes(32));
        $this->updatedAt = new \DateTime();
        return $this->apiKey;
    }

    public function __toString(): string
    {
        return $this->email ?? '';
    }
}