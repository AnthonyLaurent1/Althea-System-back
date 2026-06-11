<?php

namespace App\Entity;

use App\Repository\ChatbotMessageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChatbotMessageRepository::class)]
class ChatbotMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ChatbotConversation $conversation = null;

    #[ORM\Column(length: 20)]
    private string $sender;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $questionKey = null;

    #[ORM\Column(nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $sender = 'bot', string $content = '')
    {
        $this->sender = $sender;
        $this->content = $content;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConversation(): ?ChatbotConversation
    {
        return $this->conversation;
    }

    public function setConversation(?ChatbotConversation $conversation): static
    {
        $this->conversation = $conversation;

        return $this;
    }

    public function getSender(): string
    {
        return $this->sender;
    }

    public function setSender(string $sender): static
    {
        $this->sender = $sender;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getQuestionKey(): ?string
    {
        return $this->questionKey;
    }

    public function setQuestionKey(?string $questionKey): static
    {
        $this->questionKey = $questionKey;

        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): static
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
