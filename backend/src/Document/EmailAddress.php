<?php

declare(strict_types=1);

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ODM\EmbeddedDocument]
class EmailAddress
{
    #[ODM\Field(type: 'string')]
    #[Assert\NotBlank(message: 'Email address is required.')]
    #[Assert\Email(message: 'Please enter a valid email address.')]
    #[Assert\Length(
        min: 3,
        max: 320,
        minMessage: 'Email must be at least {{ limit }} characters long.',
        maxMessage: 'Email cannot be longer than {{ limit }} characters.'
    )]
    #[Groups(['message:read', 'message:write', 'account:read', 'account:write'])]
    private ?string $email = null;

    #[ODM\Field(type: 'string', nullable: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Name cannot be longer than {{ limit }} characters.'
    )]
    #[Groups(['message:read', 'message:write', 'account:read', 'account:write'])]
    private ?string $name = null;

    public function __construct(?string $email = null, ?string $name = null)
    {
        $this->email = $email ? strtolower(trim($email)) : null;
        $this->name = $name ? trim($name) : null;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email ? strtolower(trim($email)) : null;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name ? trim($name) : null;
        return $this;
    }

    public function getDisplayName(): string
    {
        if ($this->name) {
            return sprintf('%s <%s>', $this->name, $this->email);
        }
        return $this->email ?? '';
    }

    public function getLocalPart(): ?string
    {
        if (!$this->email) {
            return null;
        }

        $parts = explode('@', $this->email);
        return $parts[0] ?? null;
    }

    public function getDomain(): ?string
    {
        if (!$this->email) {
            return null;
        }

        $parts = explode('@', $this->email);
        return $parts[1] ?? null;
    }

    public function getPlusTag(): ?string
    {
        $localPart = $this->getLocalPart();
        if (!$localPart || !str_contains($localPart, '+')) {
            return null;
        }

        $parts = explode('+', $localPart);
        return $parts[1] ?? null;
    }

    public function getBaseAddress(): ?string
    {
        $localPart = $this->getLocalPart();
        $domain = $this->getDomain();
        
        if (!$localPart || !$domain) {
            return $this->email;
        }

        // Remove plus tag if present
        if (str_contains($localPart, '+')) {
            $parts = explode('+', $localPart);
            $localPart = $parts[0];
        }

        return $localPart . '@' . $domain;
    }

    public function isValid(): bool
    {
        return $this->email !== null && 
               filter_var($this->email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function __toString(): string
    {
        return $this->getDisplayName();
    }

    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'name' => $this->name,
            'displayName' => $this->getDisplayName()
        ];
    }

    public static function fromString(string $addressString): self
    {
        // Parse "Name <email@domain.com>" format
        if (preg_match('/^(.+?)\s*<(.+?)>$/', trim($addressString), $matches)) {
            return new self($matches[2], trim($matches[1], '"\''));
        }

        // Plain email address
        return new self($addressString);
    }
}