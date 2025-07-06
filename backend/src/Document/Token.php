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
use ApiPlatform\Doctrine\Odm\Filter\DateFilter;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ODM\Document(collection: 'tokens')]
#[ODM\Index(keys: ['emailAccount.$id' => 'asc'])]
#[ODM\Index(keys: ['token' => 'asc'], options: ['unique' => true])]
#[ODM\Index(keys: ['type' => 'asc'])]
#[ODM\Index(keys: ['isUsed' => 'asc'])]
#[ODM\Index(keys: ['expiresAt' => 'asc'])]
#[ODM\Index(keys: ['createdAt' => 'desc'])]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['token:read', 'token:list']]
        ),
        new Post(
            denormalizationContext: ['groups' => ['token:write']],
            normalizationContext: ['groups' => ['token:read']]
        ),
        new Get(
            normalizationContext: ['groups' => ['token:read', 'token:detail']]
        ),
        new Put(
            denormalizationContext: ['groups' => ['token:write']],
            normalizationContext: ['groups' => ['token:read']]
        ),
        new Patch(
            denormalizationContext: ['groups' => ['token:write']],
            normalizationContext: ['groups' => ['token:read']]
        ),
        new Delete()
    ],
    normalizationContext: ['groups' => ['token:read']],
    denormalizationContext: ['groups' => ['token:write']],
    paginationEnabled: true,
    paginationItemsPerPage: 50
)]
#[ApiFilter(SearchFilter::class, properties: ['type' => 'exact', 'emailAccount.email' => 'partial'])]
#[ApiFilter(BooleanFilter::class, properties: ['isUsed', 'isExpired'])]
#[ApiFilter(DateFilter::class, properties: ['expiresAt', 'createdAt', 'usedAt'])]
#[ApiFilter(OrderFilter::class, properties: ['type', 'createdAt', 'expiresAt', 'usedAt'])]
class Token
{
    public const TYPE_EMAIL_VERIFICATION = 'email_verification';
    public const TYPE_PASSWORD_RESET = 'password_reset';
    public const TYPE_API_ACCESS = 'api_access';
    public const TYPE_TWO_FACTOR = 'two_factor';
    public const TYPE_LOGIN = 'login';
    public const TYPE_SESSION = 'session';

    #[ODM\Id]
    #[Groups(['token:read'])]
    private ?string $id = null;

    #[ODM\ReferenceOne(targetDocument: EmailAccount::class, inversedBy: 'tokens')]
    #[Assert\NotNull(message: 'Email account is required.')]
    #[Groups(['token:read', 'token:write'])]
    private ?EmailAccount $emailAccount = null;

    #[ODM\Field(type: 'string')]
    #[Assert\NotBlank(message: 'Token value is required.')]
    #[Assert\Length(
        min: 32,
        max: 255,
        minMessage: 'Token must be at least {{ limit }} characters long.',
        maxMessage: 'Token cannot be longer than {{ limit }} characters.'
    )]
    #[Groups(['token:read', 'token:write'])]
    private ?string $token = null;

    #[ODM\Field(type: 'string')]
    #[Assert\NotBlank(message: 'Token type is required.')]
    #[Assert\Choice(
        choices: [
            self::TYPE_EMAIL_VERIFICATION,
            self::TYPE_PASSWORD_RESET,
            self::TYPE_API_ACCESS,
            self::TYPE_TWO_FACTOR,
            self::TYPE_LOGIN,
            self::TYPE_SESSION
        ],
        message: 'Invalid token type.'
    )]
    #[Groups(['token:read', 'token:write'])]
    private ?string $type = null;

    #[ODM\Field(type: 'string', nullable: true)]
    #[Assert\Length(
        max: 500,
        maxMessage: 'Description cannot be longer than {{ limit }} characters.'
    )]
    #[Groups(['token:read', 'token:write'])]
    private ?string $description = null;

    #[ODM\Field(type: 'bool')]
    #[Groups(['token:read'])]
    private bool $isUsed = false;

    #[ODM\Field(type: 'bool')]
    #[Groups(['token:read'])]
    private bool $isExpired = false;

    #[ODM\Field(type: 'date')]
    #[Assert\NotNull(message: 'Expiration date is required.')]
    #[Groups(['token:read', 'token:write'])]
    private ?\DateTimeInterface $expiresAt = null;

    #[ODM\Field(type: 'date', nullable: true)]
    #[Groups(['token:read'])]
    private ?\DateTimeInterface $usedAt = null;

    #[ODM\Field(type: 'string', nullable: true)]
    #[Groups(['token:read', 'token:detail'])]
    private ?string $ipAddress = null;

    #[ODM\Field(type: 'string', nullable: true)]
    #[Groups(['token:read', 'token:detail'])]
    private ?string $userAgent = null;

    #[ODM\Field(type: 'hash')]
    #[Groups(['token:read', 'token:detail'])]
    private array $metadata = [];

    #[ODM\Field(type: 'hash')]
    #[Groups(['token:read', 'token:detail'])]
    private array $scopes = [];

    #[ODM\Field(type: 'int')]
    #[Assert\PositiveOrZero(message: 'Usage count must be positive or zero.')]
    #[Groups(['token:read'])]
    private int $usageCount = 0;

    #[ODM\Field(type: 'int', nullable: true)]
    #[Assert\Positive(message: 'Max usage must be positive.')]
    #[Groups(['token:read', 'token:write'])]
    private ?int $maxUsage = null;

    #[ODM\Field(type: 'date', nullable: true)]
    #[Groups(['token:read'])]
    private ?\DateTimeInterface $lastUsedAt = null;

    #[ODM\Field(type: 'string', nullable: true)]
    #[Groups(['token:read', 'token:detail'])]
    private ?string $revokedBy = null;

    #[ODM\Field(type: 'date', nullable: true)]
    #[Groups(['token:read'])]
    private ?\DateTimeInterface $revokedAt = null;

    #[ODM\Field(type: 'string', nullable: true)]
    #[Groups(['token:read', 'token:detail'])]
    private ?string $revokedReason = null;

    #[ODM\Field(type: 'hash')]
    #[Groups(['token:read'])]
    private array $nightwatchMetadata = [];

    #[ODM\Field(type: 'date')]
    #[Groups(['token:read'])]
    private \DateTimeInterface $createdAt;

    #[ODM\Field(type: 'date')]
    #[Groups(['token:read'])]
    private \DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->metadata = [];
        $this->scopes = [];
        $this->nightwatchMetadata = [];
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

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): static
    {
        $this->token = $token;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
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

    public function isUsed(): bool
    {
        return $this->isUsed;
    }

    public function setIsUsed(bool $isUsed): static
    {
        $this->isUsed = $isUsed;
        if ($isUsed && !$this->usedAt) {
            $this->usedAt = new \DateTime();
        }
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function isExpired(): bool
    {
        if ($this->isExpired) {
            return true;
        }
        
        if ($this->expiresAt && $this->expiresAt < new \DateTime()) {
            $this->isExpired = true;
            $this->updatedAt = new \DateTime();
            return true;
        }
        
        return false;
    }

    public function setIsExpired(bool $isExpired): static
    {
        $this->isExpired = $isExpired;
        $this->updatedAt = new \DateTime();
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

    public function getUsedAt(): ?\DateTimeInterface
    {
        return $this->usedAt;
    }

    public function setUsedAt(?\DateTimeInterface $usedAt): static
    {
        $this->usedAt = $usedAt;
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
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        $this->userAgent = $userAgent;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): static
    {
        $this->metadata = $metadata;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function addMetadata(string $key, mixed $value): static
    {
        $this->metadata[$key] = $value;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function setScopes(array $scopes): static
    {
        $this->scopes = array_values(array_unique($scopes));
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function addScope(string $scope): static
    {
        if (!in_array($scope, $this->scopes)) {
            $this->scopes[] = $scope;
            $this->updatedAt = new \DateTime();
        }
        return $this;
    }

    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes);
    }

    public function getUsageCount(): int
    {
        return $this->usageCount;
    }

    public function setUsageCount(int $usageCount): static
    {
        $this->usageCount = max(0, $usageCount);
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function incrementUsage(): static
    {
        $this->usageCount++;
        $this->lastUsedAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getMaxUsage(): ?int
    {
        return $this->maxUsage;
    }

    public function setMaxUsage(?int $maxUsage): static
    {
        $this->maxUsage = $maxUsage;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getLastUsedAt(): ?\DateTimeInterface
    {
        return $this->lastUsedAt;
    }

    public function setLastUsedAt(?\DateTimeInterface $lastUsedAt): static
    {
        $this->lastUsedAt = $lastUsedAt;
        return $this;
    }

    public function getRevokedBy(): ?string
    {
        return $this->revokedBy;
    }

    public function setRevokedBy(?string $revokedBy): static
    {
        $this->revokedBy = $revokedBy;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getRevokedAt(): ?\DateTimeInterface
    {
        return $this->revokedAt;
    }

    public function setRevokedAt(?\DateTimeInterface $revokedAt): static
    {
        $this->revokedAt = $revokedAt;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getRevokedReason(): ?string
    {
        return $this->revokedReason;
    }

    public function setRevokedReason(?string $revokedReason): static
    {
        $this->revokedReason = $revokedReason;
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

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    // Business Logic Methods
    public function isValid(): bool
    {
        return !$this->isUsed() && 
               !$this->isExpired() && 
               !$this->isRevoked() && 
               !$this->hasExceededUsageLimit();
    }

    public function isRevoked(): bool
    {
        return $this->revokedAt !== null;
    }

    public function hasExceededUsageLimit(): bool
    {
        return $this->maxUsage !== null && $this->usageCount >= $this->maxUsage;
    }

    public function canBeUsed(): bool
    {
        return $this->isValid();
    }

    public function revoke(?string $reason = null, ?string $revokedBy = null): static
    {
        $this->revokedAt = new \DateTime();
        $this->revokedReason = $reason;
        $this->revokedBy = $revokedBy;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function use(?string $ipAddress = null, ?string $userAgent = null): static
    {
        if (!$this->canBeUsed()) {
            throw new \RuntimeException('Token cannot be used');
        }

        $this->incrementUsage();
        $this->setIsUsed(true);
        
        if ($ipAddress) {
            $this->setIpAddress($ipAddress);
        }
        
        if ($userAgent) {
            $this->setUserAgent($userAgent);
        }

        return $this;
    }

    public function getTimeUntilExpiration(): ?\DateInterval
    {
        if (!$this->expiresAt) {
            return null;
        }
        
        $now = new \DateTime();
        if ($this->expiresAt <= $now) {
            return new \DateInterval('PT0S'); // Already expired
        }
        
        return $now->diff($this->expiresAt);
    }

    public function isEmailVerificationToken(): bool
    {
        return $this->type === self::TYPE_EMAIL_VERIFICATION;
    }

    public function isPasswordResetToken(): bool
    {
        return $this->type === self::TYPE_PASSWORD_RESET;
    }

    public function isApiAccessToken(): bool
    {
        return $this->type === self::TYPE_API_ACCESS;
    }

    public function isTwoFactorToken(): bool
    {
        return $this->type === self::TYPE_TWO_FACTOR;
    }

    public function isLoginToken(): bool
    {
        return $this->type === self::TYPE_LOGIN;
    }

    public function isSessionToken(): bool
    {
        return $this->type === self::TYPE_SESSION;
    }

    public static function generateToken(int $length = 64): string
    {
        return bin2hex(random_bytes($length / 2));
    }

    public static function createEmailVerificationToken(EmailAccount $emailAccount, int $validHours = 24): self
    {
        $token = new self();
        $token->setEmailAccount($emailAccount);
        $token->setType(self::TYPE_EMAIL_VERIFICATION);
        $token->setToken(self::generateToken());
        $token->setExpiresAt(new \DateTime('+' . $validHours . ' hours'));
        $token->setDescription('Email verification token');
        return $token;
    }

    public static function createPasswordResetToken(EmailAccount $emailAccount, int $validHours = 2): self
    {
        $token = new self();
        $token->setEmailAccount($emailAccount);
        $token->setType(self::TYPE_PASSWORD_RESET);
        $token->setToken(self::generateToken());
        $token->setExpiresAt(new \DateTime('+' . $validHours . ' hours'));
        $token->setDescription('Password reset token');
        $token->setMaxUsage(1);
        return $token;
    }

    public static function createApiAccessToken(EmailAccount $emailAccount, array $scopes = [], int $validDays = 365): self
    {
        $token = new self();
        $token->setEmailAccount($emailAccount);
        $token->setType(self::TYPE_API_ACCESS);
        $token->setToken(self::generateToken(128));
        $token->setExpiresAt(new \DateTime('+' . $validDays . ' days'));
        $token->setScopes($scopes);
        $token->setDescription('API access token');
        return $token;
    }

    public function __toString(): string
    {
        return sprintf('[%s] %s - %s', $this->type, $this->token, $this->emailAccount?->getEmail() ?? 'No Account');
    }
}