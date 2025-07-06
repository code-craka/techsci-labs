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
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ODM\Document(collection: 'domains')]
#[ODM\Index(keys: ['domain' => 'asc'], options: ['unique' => true])]
#[ODM\Index(keys: ['isActive' => 'desc'])]
#[ODM\Index(keys: ['createdAt' => 'desc'])]
#[UniqueEntity(fields: ['domain'], message: 'This domain already exists.')]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['domain:read', 'domain:list']]
        ),
        new Post(
            denormalizationContext: ['groups' => ['domain:write']],
            normalizationContext: ['groups' => ['domain:read']]
        ),
        new Get(
            normalizationContext: ['groups' => ['domain:read', 'domain:detail']]
        ),
        new Put(
            denormalizationContext: ['groups' => ['domain:write']],
            normalizationContext: ['groups' => ['domain:read']]
        ),
        new Patch(
            denormalizationContext: ['groups' => ['domain:write']],
            normalizationContext: ['groups' => ['domain:read']]
        ),
        new Delete()
    ],
    normalizationContext: ['groups' => ['domain:read']],
    denormalizationContext: ['groups' => ['domain:write']],
    paginationEnabled: true,
    paginationItemsPerPage: 30
)]
#[ApiFilter(SearchFilter::class, properties: ['domain' => 'partial', 'isActive' => 'exact'])]
#[ApiFilter(OrderFilter::class, properties: ['domain', 'createdAt', 'isActive'])]
class Domain
{
    #[ODM\Id]
    #[Groups(['domain:read'])]
    private ?string $id = null;

    #[ODM\Field(type: 'string')]
    #[Assert\NotBlank(message: 'Domain name is required.')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Domain must be at least {{ limit }} characters long.',
        maxMessage: 'Domain cannot be longer than {{ limit }} characters.'
    )]
    #[Assert\Regex(
        pattern: '/^([a-z0-9]([a-z0-9\-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i',
        message: 'Please enter a valid domain name.'
    )]
    #[Groups(['domain:read', 'domain:write'])]
    private ?string $domain = null;

    #[ODM\Field(type: 'string', nullable: true)]
    #[Assert\Length(
        max: 500,
        maxMessage: 'Description cannot be longer than {{ limit }} characters.'
    )]
    #[Groups(['domain:read', 'domain:write'])]
    private ?string $description = null;

    #[ODM\Field(type: 'bool')]
    #[Groups(['domain:read', 'domain:write'])]
    private bool $isActive = true;

    #[ODM\Field(type: 'bool')]
    #[Groups(['domain:read', 'domain:write'])]
    private bool $isCatchAll = false;

    #[ODM\Field(type: 'bool')]
    #[Groups(['domain:read', 'domain:write'])]
    private bool $isPlusAliasing = true;

    #[ODM\Field(type: 'int')]
    #[Assert\PositiveOrZero(message: 'Max accounts must be zero or positive.')]
    #[Groups(['domain:read', 'domain:write'])]
    private int $maxAccounts = 1000;

    #[ODM\Field(type: 'int')]
    #[Assert\PositiveOrZero(message: 'Max storage must be zero or positive.')]
    #[Groups(['domain:read', 'domain:write'])]
    private int $maxStorageMb = 10240; // 10GB default

    #[ODM\Field(type: 'collection')]
    #[Groups(['domain:read', 'domain:write'])]
    private array $dnsRecords = [];

    #[ODM\Field(type: 'string', nullable: true)]
    #[Groups(['domain:read', 'domain:write'])]
    private ?string $smtpHost = null;

    #[ODM\Field(type: 'int', nullable: true)]
    #[Assert\Range(
        min: 1,
        max: 65535,
        notInRangeMessage: 'SMTP port must be between {{ min }} and {{ max }}.'
    )]
    #[Groups(['domain:read', 'domain:write'])]
    private ?int $smtpPort = null;

    #[ODM\Field(type: 'bool')]
    #[Groups(['domain:read', 'domain:write'])]
    private bool $smtpTls = true;

    #[ODM\Field(type: 'hash')]
    #[Groups(['domain:read', 'domain:detail'])]
    private array $statistics = [
        'totalAccounts' => 0,
        'activeAccounts' => 0,
        'totalMessages' => 0,
        'totalStorageMb' => 0,
        'lastActivity' => null
    ];

    #[ODM\Field(type: 'hash')]
    #[Groups(['domain:read'])]
    private array $nightwatchMetadata = [];

    #[ODM\Field(type: 'date')]
    #[Groups(['domain:read'])]
    private \DateTimeInterface $createdAt;

    #[ODM\Field(type: 'date')]
    #[Groups(['domain:read'])]
    private \DateTimeInterface $updatedAt;

    #[ODM\ReferenceMany(targetDocument: EmailAccount::class, mappedBy: 'domain')]
    #[Groups(['domain:detail'])]
    private Collection $emailAccounts;

    public function __construct()
    {
        $this->emailAccounts = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->dnsRecords = [];
        $this->nightwatchMetadata = [];
        $this->statistics = [
            'totalAccounts' => 0,
            'activeAccounts' => 0,
            'totalMessages' => 0,
            'totalStorageMb' => 0,
            'lastActivity' => null
        ];
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function setDomain(string $domain): static
    {
        $this->domain = strtolower(trim($domain));
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

    public function isCatchAll(): bool
    {
        return $this->isCatchAll;
    }

    public function setIsCatchAll(bool $isCatchAll): static
    {
        $this->isCatchAll = $isCatchAll;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function isPlusAliasing(): bool
    {
        return $this->isPlusAliasing;
    }

    public function setIsPlusAliasing(bool $isPlusAliasing): static
    {
        $this->isPlusAliasing = $isPlusAliasing;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getMaxAccounts(): int
    {
        return $this->maxAccounts;
    }

    public function setMaxAccounts(int $maxAccounts): static
    {
        $this->maxAccounts = $maxAccounts;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getMaxStorageMb(): int
    {
        return $this->maxStorageMb;
    }

    public function setMaxStorageMb(int $maxStorageMb): static
    {
        $this->maxStorageMb = $maxStorageMb;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getDnsRecords(): array
    {
        return $this->dnsRecords;
    }

    public function setDnsRecords(array $dnsRecords): static
    {
        $this->dnsRecords = $dnsRecords;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function addDnsRecord(array $record): static
    {
        $this->dnsRecords[] = $record;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getSmtpHost(): ?string
    {
        return $this->smtpHost;
    }

    public function setSmtpHost(?string $smtpHost): static
    {
        $this->smtpHost = $smtpHost;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getSmtpPort(): ?int
    {
        return $this->smtpPort;
    }

    public function setSmtpPort(?int $smtpPort): static
    {
        $this->smtpPort = $smtpPort;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function isSmtpTls(): bool
    {
        return $this->smtpTls;
    }

    public function setSmtpTls(bool $smtpTls): static
    {
        $this->smtpTls = $smtpTls;
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

    public function addNightwatchMetadata(string $key, mixed $value): static
    {
        $this->nightwatchMetadata[$key] = $value;
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

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * @return Collection<int, EmailAccount>
     */
    public function getEmailAccounts(): Collection
    {
        return $this->emailAccounts;
    }

    public function addEmailAccount(EmailAccount $emailAccount): static
    {
        if (!$this->emailAccounts->contains($emailAccount)) {
            $this->emailAccounts->add($emailAccount);
            $emailAccount->setDomain($this);
        }

        return $this;
    }

    public function removeEmailAccount(EmailAccount $emailAccount): static
    {
        if ($this->emailAccounts->removeElement($emailAccount)) {
            // set the owning side to null (unless already changed)
            if ($emailAccount->getDomain() === $this) {
                $emailAccount->setDomain(null);
            }
        }

        return $this;
    }

    /**
     * Check if domain can accept new email accounts
     */
    public function canCreateAccount(): bool
    {
        return $this->isActive && 
               $this->emailAccounts->count() < $this->maxAccounts;
    }

    /**
     * Check if domain has storage capacity
     */
    public function hasStorageCapacity(int $additionalMb = 0): bool
    {
        $currentStorage = $this->statistics['totalStorageMb'] ?? 0;
        return ($currentStorage + $additionalMb) <= $this->maxStorageMb;
    }

    /**
     * Get domain validation status for DNS records
     */
    public function isValidated(): bool
    {
        foreach ($this->dnsRecords as $record) {
            if (isset($record['type']) && $record['type'] === 'MX' && isset($record['verified']) && $record['verified']) {
                return true;
            }
        }
        return false;
    }

    public function __toString(): string
    {
        return $this->domain ?? '';
    }
}