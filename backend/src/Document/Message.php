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
use ApiPlatform\Doctrine\Odm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Odm\Filter\DateFilter;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ODM\Document(collection: 'messages')]
#[ODM\Index(keys: ['mailbox.$id' => 'asc', 'uid' => 'asc'])]
#[ODM\Index(keys: ['messageId' => 'asc'])]
#[ODM\Index(keys: ['from.email' => 'asc'])]
#[ODM\Index(keys: ['to.email' => 'asc'])]
#[ODM\Index(keys: ['subject' => 'text'])]
#[ODM\Index(keys: ['date' => 'desc'])]
#[ODM\Index(keys: ['flags' => 'asc'])]
#[ODM\Index(keys: ['isRead' => 'asc'])]
#[ODM\Index(keys: ['createdAt' => 'desc'])]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['message:read', 'message:list']]
        ),
        new Post(
            denormalizationContext: ['groups' => ['message:write']],
            normalizationContext: ['groups' => ['message:read']]
        ),
        new Get(
            normalizationContext: ['groups' => ['message:read', 'message:detail']]
        ),
        new Put(
            denormalizationContext: ['groups' => ['message:write']],
            normalizationContext: ['groups' => ['message:read']]
        ),
        new Patch(
            denormalizationContext: ['groups' => ['message:write']],
            normalizationContext: ['groups' => ['message:read']]
        ),
        new Delete()
    ],
    normalizationContext: ['groups' => ['message:read']],
    denormalizationContext: ['groups' => ['message:write']],
    paginationEnabled: true,
    paginationItemsPerPage: 50
)]
#[ApiFilter(SearchFilter::class, properties: [
    'subject' => 'partial', 
    'from.email' => 'partial', 
    'to.email' => 'partial',
    'messageId' => 'exact'
])]
#[ApiFilter(BooleanFilter::class, properties: ['isRead', 'isFlagged', 'isDeleted', 'isSpam'])]
#[ApiFilter(DateFilter::class, properties: ['date', 'createdAt'])]
#[ApiFilter(RangeFilter::class, properties: ['sizeMb'])]
#[ApiFilter(OrderFilter::class, properties: ['date', 'subject', 'sizeMb', 'createdAt'])]
class Message
{
    #[ODM\Id]
    #[Groups(['message:read'])]
    private ?string $id = null;

    #[ODM\ReferenceOne(targetDocument: Mailbox::class, inversedBy: 'messages')]
    #[Assert\NotNull(message: 'Mailbox is required.')]
    #[Groups(['message:read', 'message:write'])]
    private ?Mailbox $mailbox = null;

    #[ODM\Field(type: 'int')]
    #[Assert\Positive(message: 'UID must be positive.')]
    #[Groups(['message:read', 'message:write'])]
    private int $uid = 0;

    #[ODM\Field(type: 'string')]
    #[Assert\NotBlank(message: 'Message ID is required.')]
    #[Assert\Length(
        min: 1,
        max: 255,
        minMessage: 'Message ID must be at least {{ limit }} character long.',
        maxMessage: 'Message ID cannot be longer than {{ limit }} characters.'
    )]
    #[Groups(['message:read', 'message:write'])]
    private ?string $messageId = null;

    #[ODM\Field(type: 'string', nullable: true)]
    #[Groups(['message:read', 'message:write'])]
    private ?string $inReplyTo = null;

    #[ODM\Field(type: 'collection')]
    #[Groups(['message:read', 'message:write'])]
    private array $references = [];

    #[ODM\Field(type: 'string', nullable: true)]
    #[Assert\Length(
        max: 998,
        maxMessage: 'Subject cannot be longer than {{ limit }} characters.'
    )]
    #[Groups(['message:read', 'message:write'])]
    private ?string $subject = null;

    #[ODM\EmbedOne(targetDocument: EmailAddress::class)]
    #[Groups(['message:read', 'message:write'])]
    private ?EmailAddress $from = null;

    #[ODM\EmbedOne(targetDocument: EmailAddress::class)]
    #[Groups(['message:read', 'message:write'])]
    private ?EmailAddress $sender = null;

    #[ODM\EmbedOne(targetDocument: EmailAddress::class)]
    #[Groups(['message:read', 'message:write'])]
    private ?EmailAddress $replyTo = null;

    #[ODM\EmbedMany(targetDocument: EmailAddress::class)]
    #[Groups(['message:read', 'message:write'])]
    private array $to = [];

    #[ODM\EmbedMany(targetDocument: EmailAddress::class)]
    #[Groups(['message:read', 'message:write'])]
    private array $cc = [];

    #[ODM\EmbedMany(targetDocument: EmailAddress::class)]
    #[Groups(['message:read', 'message:write'])]
    private array $bcc = [];

    #[ODM\Field(type: 'date')]
    #[Groups(['message:read', 'message:write'])]
    private ?\DateTimeInterface $date = null;

    #[ODM\Field(type: 'hash')]
    #[Groups(['message:read', 'message:detail'])]
    private array $headers = [];

    #[ODM\Field(type: 'string', nullable: true)]
    #[Groups(['message:read', 'message:detail'])]
    private ?string $textBody = null;

    #[ODM\Field(type: 'string', nullable: true)]
    #[Groups(['message:read', 'message:detail'])]
    private ?string $htmlBody = null;

    #[ODM\Field(type: 'string', nullable: true)]
    #[Groups(['message:read', 'message:detail'])]
    private ?string $rawContent = null;

    #[ODM\Field(type: 'collection')]
    #[Groups(['message:read'])]
    private array $flags = [];

    #[ODM\Field(type: 'bool')]
    #[Groups(['message:read', 'message:write'])]
    private bool $isRead = false;

    #[ODM\Field(type: 'bool')]
    #[Groups(['message:read', 'message:write'])]
    private bool $isFlagged = false;

    #[ODM\Field(type: 'bool')]
    #[Groups(['message:read', 'message:write'])]
    private bool $isAnswered = false;

    #[ODM\Field(type: 'bool')]
    #[Groups(['message:read', 'message:write'])]
    private bool $isDeleted = false;

    #[ODM\Field(type: 'bool')]
    #[Groups(['message:read', 'message:write'])]
    private bool $isDraft = false;

    #[ODM\Field(type: 'bool')]
    #[Groups(['message:read', 'message:write'])]
    private bool $isRecent = false;

    #[ODM\Field(type: 'bool')]
    #[Groups(['message:read', 'message:write'])]
    private bool $isSpam = false;

    #[ODM\Field(type: 'float')]
    #[Groups(['message:read'])]
    private float $sizeMb = 0.0;

    #[ODM\Field(type: 'int')]
    #[Groups(['message:read'])]
    private int $sizeBytes = 0;

    #[ODM\Field(type: 'string', nullable: true)]
    #[Groups(['message:read', 'message:write'])]
    private ?string $contentType = null;

    #[ODM\Field(type: 'string', nullable: true)]
    #[Groups(['message:read', 'message:write'])]
    private ?string $encoding = null;

    #[ODM\Field(type: 'string', nullable: true)]
    #[Groups(['message:read', 'message:write'])]
    private ?string $charset = null;

    #[ODM\Field(type: 'bool')]
    #[Groups(['message:read'])]
    private bool $hasAttachments = false;

    #[ODM\Field(type: 'int')]
    #[Groups(['message:read'])]
    private int $attachmentCount = 0;

    #[ODM\Field(type: 'hash')]
    #[Groups(['message:read', 'message:detail'])]
    private array $securityInfo = [
        'spfStatus' => null,
        'dkimStatus' => null,
        'dmarcStatus' => null,
        'virusStatus' => null,
        'spamScore' => null
    ];

    #[ODM\Field(type: 'hash')]
    #[Groups(['message:read'])]
    private array $nightwatchMetadata = [];

    #[ODM\Field(type: 'date')]
    #[Groups(['message:read'])]
    private \DateTimeInterface $createdAt;

    #[ODM\Field(type: 'date')]
    #[Groups(['message:read'])]
    private \DateTimeInterface $updatedAt;

    #[ODM\ReferenceMany(targetDocument: Attachment::class, mappedBy: 'message')]
    #[Groups(['message:detail'])]
    private Collection $attachments;

    public function __construct()
    {
        $this->attachments = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->date = new \DateTime();
        $this->to = [];
        $this->cc = [];
        $this->bcc = [];
        $this->references = [];
        $this->headers = [];
        $this->flags = [];
        $this->nightwatchMetadata = [];
        $this->securityInfo = [
            'spfStatus' => null,
            'dkimStatus' => null,
            'dmarcStatus' => null,
            'virusStatus' => null,
            'spamScore' => null
        ];
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getMailbox(): ?Mailbox
    {
        return $this->mailbox;
    }

    public function setMailbox(?Mailbox $mailbox): static
    {
        $this->mailbox = $mailbox;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getUid(): int
    {
        return $this->uid;
    }

    public function setUid(int $uid): static
    {
        $this->uid = $uid;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getMessageId(): ?string
    {
        return $this->messageId;
    }

    public function setMessageId(string $messageId): static
    {
        $this->messageId = $messageId;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getInReplyTo(): ?string
    {
        return $this->inReplyTo;
    }

    public function setInReplyTo(?string $inReplyTo): static
    {
        $this->inReplyTo = $inReplyTo;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getReferences(): array
    {
        return $this->references;
    }

    public function setReferences(array $references): static
    {
        $this->references = array_values(array_unique($references));
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function addReference(string $reference): static
    {
        if (!in_array($reference, $this->references)) {
            $this->references[] = $reference;
            $this->updatedAt = new \DateTime();
        }
        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): static
    {
        $this->subject = $subject;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getFrom(): ?EmailAddress
    {
        return $this->from;
    }

    public function setFrom(?EmailAddress $from): static
    {
        $this->from = $from;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getSender(): ?EmailAddress
    {
        return $this->sender;
    }

    public function setSender(?EmailAddress $sender): static
    {
        $this->sender = $sender;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getReplyTo(): ?EmailAddress
    {
        return $this->replyTo;
    }

    public function setReplyTo(?EmailAddress $replyTo): static
    {
        $this->replyTo = $replyTo;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getTo(): array
    {
        return $this->to;
    }

    public function setTo(array $to): static
    {
        $this->to = $to;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function addTo(EmailAddress $to): static
    {
        $this->to[] = $to;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getCc(): array
    {
        return $this->cc;
    }

    public function setCc(array $cc): static
    {
        $this->cc = $cc;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function addCc(EmailAddress $cc): static
    {
        $this->cc[] = $cc;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getBcc(): array
    {
        return $this->bcc;
    }

    public function setBcc(array $bcc): static
    {
        $this->bcc = $bcc;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function addBcc(EmailAddress $bcc): static
    {
        $this->bcc[] = $bcc;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): static
    {
        $this->date = $date;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setHeaders(array $headers): static
    {
        $this->headers = $headers;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getHeader(string $name, mixed $default = null): mixed
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    public function setHeader(string $name, mixed $value): static
    {
        $this->headers[strtolower($name)] = $value;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getTextBody(): ?string
    {
        return $this->textBody;
    }

    public function setTextBody(?string $textBody): static
    {
        $this->textBody = $textBody;
        $this->updateSize();
        return $this;
    }

    public function getHtmlBody(): ?string
    {
        return $this->htmlBody;
    }

    public function setHtmlBody(?string $htmlBody): static
    {
        $this->htmlBody = $htmlBody;
        $this->updateSize();
        return $this;
    }

    public function getRawContent(): ?string
    {
        return $this->rawContent;
    }

    public function setRawContent(?string $rawContent): static
    {
        $this->rawContent = $rawContent;
        $this->updateSize();
        return $this;
    }

    public function getFlags(): array
    {
        return $this->flags;
    }

    public function setFlags(array $flags): static
    {
        $this->flags = array_values(array_unique($flags));
        $this->updateFlagProperties();
        return $this;
    }

    public function addFlag(string $flag): static
    {
        if (!in_array($flag, $this->flags)) {
            $this->flags[] = $flag;
            $this->updateFlagProperties();
        }
        return $this;
    }

    public function removeFlag(string $flag): static
    {
        $this->flags = array_values(array_filter($this->flags, fn($f) => $f !== $flag));
        $this->updateFlagProperties();
        return $this;
    }

    private function updateFlagProperties(): void
    {
        $this->isRead = in_array('\\Seen', $this->flags);
        $this->isFlagged = in_array('\\Flagged', $this->flags);
        $this->isAnswered = in_array('\\Answered', $this->flags);
        $this->isDeleted = in_array('\\Deleted', $this->flags);
        $this->isDraft = in_array('\\Draft', $this->flags);
        $this->isRecent = in_array('\\Recent', $this->flags);
        $this->updatedAt = new \DateTime();
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): static
    {
        $this->isRead = $isRead;
        if ($isRead && !in_array('\\Seen', $this->flags)) {
            $this->addFlag('\\Seen');
        } elseif (!$isRead) {
            $this->removeFlag('\\Seen');
        }
        return $this;
    }

    public function isFlagged(): bool
    {
        return $this->isFlagged;
    }

    public function setIsFlagged(bool $isFlagged): static
    {
        $this->isFlagged = $isFlagged;
        if ($isFlagged && !in_array('\\Flagged', $this->flags)) {
            $this->addFlag('\\Flagged');
        } elseif (!$isFlagged) {
            $this->removeFlag('\\Flagged');
        }
        return $this;
    }

    public function isAnswered(): bool
    {
        return $this->isAnswered;
    }

    public function setIsAnswered(bool $isAnswered): static
    {
        $this->isAnswered = $isAnswered;
        if ($isAnswered && !in_array('\\Answered', $this->flags)) {
            $this->addFlag('\\Answered');
        } elseif (!$isAnswered) {
            $this->removeFlag('\\Answered');
        }
        return $this;
    }

    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(bool $isDeleted): static
    {
        $this->isDeleted = $isDeleted;
        if ($isDeleted && !in_array('\\Deleted', $this->flags)) {
            $this->addFlag('\\Deleted');
        } elseif (!$isDeleted) {
            $this->removeFlag('\\Deleted');
        }
        return $this;
    }

    public function isDraft(): bool
    {
        return $this->isDraft;
    }

    public function setIsDraft(bool $isDraft): static
    {
        $this->isDraft = $isDraft;
        if ($isDraft && !in_array('\\Draft', $this->flags)) {
            $this->addFlag('\\Draft');
        } elseif (!$isDraft) {
            $this->removeFlag('\\Draft');
        }
        return $this;
    }

    public function isRecent(): bool
    {
        return $this->isRecent;
    }

    public function setIsRecent(bool $isRecent): static
    {
        $this->isRecent = $isRecent;
        if ($isRecent && !in_array('\\Recent', $this->flags)) {
            $this->addFlag('\\Recent');
        } elseif (!$isRecent) {
            $this->removeFlag('\\Recent');
        }
        return $this;
    }

    public function isSpam(): bool
    {
        return $this->isSpam;
    }

    public function setIsSpam(bool $isSpam): static
    {
        $this->isSpam = $isSpam;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getSizeMb(): float
    {
        return $this->sizeMb;
    }

    public function getSizeBytes(): int
    {
        return $this->sizeBytes;
    }

    private function updateSize(): void
    {
        $size = 0;
        if ($this->rawContent) {
            $size = strlen($this->rawContent);
        } else {
            $size += strlen($this->textBody ?? '');
            $size += strlen($this->htmlBody ?? '');
            $size += strlen(json_encode($this->headers));
        }
        
        $this->sizeBytes = $size;
        $this->sizeMb = round($size / (1024 * 1024), 6);
        $this->updatedAt = new \DateTime();
    }

    public function getContentType(): ?string
    {
        return $this->contentType;
    }

    public function setContentType(?string $contentType): static
    {
        $this->contentType = $contentType;
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

    public function hasAttachments(): bool
    {
        return $this->hasAttachments;
    }

    public function getAttachmentCount(): int
    {
        return $this->attachmentCount;
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

    /**
     * @return Collection<int, Attachment>
     */
    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    public function addAttachment(Attachment $attachment): static
    {
        if (!$this->attachments->contains($attachment)) {
            $this->attachments->add($attachment);
            $attachment->setMessage($this);
            $this->updateAttachmentStats();
        }
        return $this;
    }

    public function removeAttachment(Attachment $attachment): static
    {
        if ($this->attachments->removeElement($attachment)) {
            if ($attachment->getMessage() === $this) {
                $attachment->setMessage(null);
            }
            $this->updateAttachmentStats();
        }
        return $this;
    }

    private function updateAttachmentStats(): void
    {
        $this->attachmentCount = $this->attachments->count();
        $this->hasAttachments = $this->attachmentCount > 0;
        $this->updatedAt = new \DateTime();
    }

    // Business Logic Methods
    public function getAllRecipients(): array
    {
        return array_merge($this->to, $this->cc, $this->bcc);
    }

    public function getPreview(int $length = 150): string
    {
        $content = $this->textBody ?? strip_tags($this->htmlBody ?? '');
        $content = preg_replace('/\s+/', ' ', trim($content));
        
        if (strlen($content) <= $length) {
            return $content;
        }
        
        return substr($content, 0, $length) . '...';
    }

    public function hasBody(): bool
    {
        return !empty($this->textBody) || !empty($this->htmlBody);
    }

    public function isMultipart(): bool
    {
        return str_starts_with($this->contentType ?? '', 'multipart/');
    }

    public function getAge(): \DateInterval
    {
        return (new \DateTime())->diff($this->date ?? $this->createdAt);
    }

    public function isOlderThan(\DateInterval $interval): bool
    {
        $threshold = (new \DateTime())->sub($interval);
        return ($this->date ?? $this->createdAt) < $threshold;
    }

    public function generateMessageId(string $domain = 'techsci.dev'): string
    {
        if (!$this->messageId) {
            $this->messageId = '<' . uniqid() . '@' . $domain . '>';
            $this->updatedAt = new \DateTime();
        }
        return $this->messageId;
    }

    public function __toString(): string
    {
        return sprintf(
            '[%s] %s from %s',
            $this->uid,
            $this->subject ?? '(No Subject)',
            $this->from?->getEmail() ?? 'Unknown'
        );
    }
}