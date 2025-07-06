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
use ApiPlatform\Doctrine\Odm\Filter\RangeFilter;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ODM\Document(collection: 'attachments')]
#[ODM\Index(keys: ['message.$id' => 'asc'])]
#[ODM\Index(keys: ['filename' => 'asc'])]
#[ODM\Index(keys: ['contentType' => 'asc'])]
#[ODM\Index(keys: ['sizeMb' => 'desc'])]
#[ODM\Index(keys: ['createdAt' => 'desc'])]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['attachment:read', 'attachment:list']]
        ),
        new Post(
            denormalizationContext: ['groups' => ['attachment:write']],
            normalizationContext: ['groups' => ['attachment:read']]
        ),
        new Get(
            normalizationContext: ['groups' => ['attachment:read', 'attachment:detail']]
        ),
        new Put(
            denormalizationContext: ['groups' => ['attachment:write']],
            normalizationContext: ['groups' => ['attachment:read']]
        ),
        new Patch(
            denormalizationContext: ['groups' => ['attachment:write']],
            normalizationContext: ['groups' => ['attachment:read']]
        ),
        new Delete()
    ],
    normalizationContext: ['groups' => ['attachment:read']],
    denormalizationContext: ['groups' => ['attachment:write']],
    paginationEnabled: true,
    paginationItemsPerPage: 50
)]
#[ApiFilter(SearchFilter::class, properties: [
    'filename' => 'partial', 
    'contentType' => 'partial'
])]
#[ApiFilter(RangeFilter::class, properties: ['sizeMb', 'sizeBytes'])]
#[ApiFilter(OrderFilter::class, properties: ['filename', 'sizeMb', 'createdAt'])]
class Attachment
{
    #[ODM\Id]
    #[Groups(['attachment:read'])]
    private ?string $id = null;

    #[ODM\ReferenceOne(targetDocument: Message::class, inversedBy: 'attachments')]
    #[Assert\NotNull(message: 'Message is required.')]
    #[Groups(['attachment:read', 'attachment:write'])]
    private ?Message $message = null;

    #[ODM\Field(type: 'string')]
    #[Assert\NotBlank(message: 'Filename is required.')]
    #[Assert\Length(
        min: 1,
        max: 255,
        minMessage: 'Filename must be at least {{ limit }} character long.',
        maxMessage: 'Filename cannot be longer than {{ limit }} characters.'
    )]
    #[Groups(['attachment:read', 'attachment:write'])]
    private ?string $filename = null;

    #[ODM\Field(type: 'string', nullable: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Original filename cannot be longer than {{ limit }} characters.'
    )]
    #[Groups(['attachment:read', 'attachment:write'])]
    private ?string $originalFilename = null;

    #[ODM\Field(type: 'string')]
    #[Assert\NotBlank(message: 'Content type is required.')]
    #[Groups(['attachment:read', 'attachment:write'])]
    private ?string $contentType = null;

    #[ODM\Field(type: 'string', nullable: true)]
    #[Groups(['attachment:read', 'attachment:write'])]
    private ?string $encoding = null;

    #[ODM\Field(type: 'string', nullable: true)]
    #[Groups(['attachment:read', 'attachment:write'])]
    private ?string $charset = null;

    #[ODM\Field(type: 'int')]
    #[Assert\PositiveOrZero(message: 'Size must be positive or zero.')]
    #[Groups(['attachment:read'])]
    private int $sizeBytes = 0;

    #[ODM\Field(type: 'float')]
    #[Groups(['attachment:read'])]
    private float $sizeMb = 0.0;

    #[ODM\Field(type: 'string', nullable: true)]
    #[Groups(['attachment:read', 'attachment:write'])]
    private ?string $contentId = null;

    #[ODM\Field(type: 'string', nullable: true)]
    #[Groups(['attachment:read', 'attachment:write'])]
    private ?string $contentDisposition = null;

    #[ODM\Field(type: 'bool')]
    #[Groups(['attachment:read', 'attachment:write'])]
    private bool $isInline = false;

    #[ODM\Field(type: 'bool')]
    #[Groups(['attachment:read'])]
    private bool $isImage = false;

    #[ODM\Field(type: 'bool')]
    #[Groups(['attachment:read'])]
    private bool $isDocument = false;

    #[ODM\Field(type: 'bool')]
    #[Groups(['attachment:read'])]
    private bool $isArchive = false;

    #[ODM\Field(type: 'string', nullable: true)]
    #[Groups(['attachment:read', 'attachment:detail'])]
    private ?string $filePath = null;

    #[ODM\Field(type: 'string', nullable: true)]
    #[Groups(['attachment:read', 'attachment:detail'])]
    private ?string $storageKey = null;

    #[ODM\Field(type: 'string', nullable: true)]
    #[Groups(['attachment:read', 'attachment:detail'])]
    private ?string $md5Hash = null;

    #[ODM\Field(type: 'string', nullable: true)]
    #[Groups(['attachment:read', 'attachment:detail'])]
    private ?string $sha256Hash = null;

    #[ODM\Field(type: 'hash')]
    #[Groups(['attachment:read', 'attachment:detail'])]
    private array $metadata = [];

    #[ODM\Field(type: 'hash')]
    #[Groups(['attachment:read', 'attachment:detail'])]
    private array $securityInfo = [
        'virusScanStatus' => null,
        'virusScanAt' => null,
        'quarantined' => false,
        'safeToDownload' => true
    ];

    #[ODM\Field(type: 'hash')]
    #[Groups(['attachment:read'])]
    private array $nightwatchMetadata = [];

    #[ODM\Field(type: 'date')]
    #[Groups(['attachment:read'])]
    private \DateTimeInterface $createdAt;

    #[ODM\Field(type: 'date')]
    #[Groups(['attachment:read'])]
    private \DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->metadata = [];
        $this->nightwatchMetadata = [];
        $this->securityInfo = [
            'virusScanStatus' => null,
            'virusScanAt' => null,
            'quarantined' => false,
            'safeToDownload' => true
        ];
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getMessage(): ?Message
    {
        return $this->message;
    }

    public function setMessage(?Message $message): static
    {
        $this->message = $message;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): static
    {
        $this->filename = $filename;
        $this->detectFileType();
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getOriginalFilename(): ?string
    {
        return $this->originalFilename;
    }

    public function setOriginalFilename(?string $originalFilename): static
    {
        $this->originalFilename = $originalFilename;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getContentType(): ?string
    {
        return $this->contentType;
    }

    public function setContentType(string $contentType): static
    {
        $this->contentType = $contentType;
        $this->detectFileType();
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getEncoding(): ?string
    {
        return $this->encoding;
    }

    public function setEncoding(?string $encoding): static
    {
        $this->encoding = $encoding;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getCharset(): ?string
    {
        return $this->charset;
    }

    public function setCharset(?string $charset): static
    {
        $this->charset = $charset;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getSizeBytes(): int
    {
        return $this->sizeBytes;
    }

    public function setSizeBytes(int $sizeBytes): static
    {
        $this->sizeBytes = max(0, $sizeBytes);
        $this->sizeMb = round($sizeBytes / (1024 * 1024), 6);
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getSizeMb(): float
    {
        return $this->sizeMb;
    }

    public function getContentId(): ?string
    {
        return $this->contentId;
    }

    public function setContentId(?string $contentId): static
    {
        $this->contentId = $contentId;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getContentDisposition(): ?string
    {
        return $this->contentDisposition;
    }

    public function setContentDisposition(?string $contentDisposition): static
    {
        $this->contentDisposition = $contentDisposition;
        $this->isInline = str_contains(strtolower($contentDisposition ?? ''), 'inline');
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function isInline(): bool
    {
        return $this->isInline;
    }

    public function setIsInline(bool $isInline): static
    {
        $this->isInline = $isInline;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function isImage(): bool
    {
        return $this->isImage;
    }

    public function isDocument(): bool
    {
        return $this->isDocument;
    }

    public function isArchive(): bool
    {
        return $this->isArchive;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): static
    {
        $this->filePath = $filePath;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getStorageKey(): ?string
    {
        return $this->storageKey;
    }

    public function setStorageKey(?string $storageKey): static
    {
        $this->storageKey = $storageKey;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getMd5Hash(): ?string
    {
        return $this->md5Hash;
    }

    public function setMd5Hash(?string $md5Hash): static
    {
        $this->md5Hash = $md5Hash;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getSha256Hash(): ?string
    {
        return $this->sha256Hash;
    }

    public function setSha256Hash(?string $sha256Hash): static
    {
        $this->sha256Hash = $sha256Hash;
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

    public function getSecurityInfo(): array
    {
        return $this->securityInfo;
    }

    public function setSecurityInfo(array $securityInfo): static
    {
        $this->securityInfo = array_merge($this->securityInfo, $securityInfo);
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function setSecurityCheck(string $type, mixed $value): static
    {
        $this->securityInfo[$type] = $value;
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

    private function detectFileType(): void
    {
        $contentType = strtolower($this->contentType ?? '');
        $filename = strtolower($this->filename ?? '');
        
        // Reset flags
        $this->isImage = false;
        $this->isDocument = false;
        $this->isArchive = false;

        // Detect by content type
        if (str_starts_with($contentType, 'image/')) {
            $this->isImage = true;
        } elseif (in_array($contentType, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'text/csv'
        ])) {
            $this->isDocument = true;
        } elseif (in_array($contentType, [
            'application/zip',
            'application/x-zip-compressed',
            'application/x-rar-compressed',
            'application/x-7z-compressed',
            'application/gzip',
            'application/x-tar'
        ])) {
            $this->isArchive = true;
        }

        // Detect by file extension if content type detection failed
        if (!$this->isImage && !$this->isDocument && !$this->isArchive) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            
            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'ico'])) {
                $this->isImage = true;
            } elseif (in_array($extension, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'rtf', 'odt', 'ods', 'odp'])) {
                $this->isDocument = true;
            } elseif (in_array($extension, ['zip', 'rar', '7z', 'tar', 'gz', 'bz2', 'xz'])) {
                $this->isArchive = true;
            }
        }
    }

    // Business Logic Methods
    public function getFileExtension(): ?string
    {
        if (!$this->filename) {
            return null;
        }
        
        return strtolower(pathinfo($this->filename, PATHINFO_EXTENSION));
    }

    public function getFormattedSize(): string
    {
        if ($this->sizeBytes < 1024) {
            return $this->sizeBytes . ' B';
        } elseif ($this->sizeBytes < 1024 * 1024) {
            return round($this->sizeBytes / 1024, 1) . ' KB';
        } elseif ($this->sizeBytes < 1024 * 1024 * 1024) {
            return round($this->sizeBytes / (1024 * 1024), 1) . ' MB';
        } else {
            return round($this->sizeBytes / (1024 * 1024 * 1024), 1) . ' GB';
        }
    }

    public function isSafeToDownload(): bool
    {
        return $this->securityInfo['safeToDownload'] ?? true;
    }

    public function isQuarantined(): bool
    {
        return $this->securityInfo['quarantined'] ?? false;
    }

    public function getVirusScanStatus(): ?string
    {
        return $this->securityInfo['virusScanStatus'] ?? null;
    }

    public function generateStorageKey(): string
    {
        if (!$this->storageKey) {
            $this->storageKey = date('Y/m/d/') . uniqid() . '_' . md5($this->filename ?? '');
            $this->updatedAt = new \DateTime();
        }
        return $this->storageKey;
    }

    public function __toString(): string
    {
        return $this->filename ?? 'Unknown Attachment';
    }
}