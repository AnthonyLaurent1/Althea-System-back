<?php

namespace App\Entity;

use App\Repository\ChatbotLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChatbotLogRepository::class)]
class ChatbotLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $sessionId = null;

    #[ORM\Column(type: 'text')]
    private ?string $userMessage = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $botResponse = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $matchedIntent = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(length: 5)]
    private string $locale = 'fr';

    #[ORM\Column]
    private bool $escalated = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    public function setSessionId(string $sessionId): static
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    public function getUserMessage(): ?string
    {
        return $this->userMessage;
    }

    public function setUserMessage(string $userMessage): static
    {
        $this->userMessage = $userMessage;

        return $this;
    }

    public function getBotResponse(): ?string
    {
        return $this->botResponse;
    }

    public function setBotResponse(?string $botResponse): static
    {
        $this->botResponse = $botResponse;

        return $this;
    }

    public function getMatchedIntent(): ?string
    {
        return $this->matchedIntent;
    }

    public function setMatchedIntent(?string $matchedIntent): static
    {
        $this->matchedIntent = $matchedIntent;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function isEscalated(): bool
    {
        return $this->escalated;
    }

    public function setEscalated(bool $escalated): static
    {
        $this->escalated = $escalated;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
