<?php

namespace App\Service;

use App\Entity\ChatbotLog;
use App\Repository\ChatbotLogRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ChatbotLogService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ChatbotLogRepository $chatbotLogRepository,
        private readonly FaqChatbotService $faqChatbotService,
    ) {
    }

    /**
     * Traite un message utilisateur : calcule la réponse FAQ et enregistre l'échange.
     *
     * @return array<string, mixed>
     */
    public function handleMessage(string $sessionId, string $message, string $locale): array
    {
        $sessionId = $this->resolveSessionId($sessionId);
        $message = trim($message);
        if ($message === '') {
            throw new \InvalidArgumentException('Le message est requis.');
        }

        $response = $this->faqChatbotService->getResponse($message, $locale);

        $log = new ChatbotLog();
        $log->setSessionId($sessionId);
        $log->setUserMessage($message);
        $log->setBotResponse((string) ($response['answer'] ?? ''));
        $log->setMatchedIntent($response['matchedIntent'] ?? null);
        $log->setCategory($response['category'] ?? null);
        $log->setLocale((string) ($response['locale'] ?? $locale));

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        return [
            'sessionId' => $sessionId,
            'logId' => $log->getId(),
            'response' => $response,
        ];
    }

    /**
     * Enregistre un échange déjà construit côté client.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function log(array $data): array
    {
        $message = trim((string) ($data['userMessage'] ?? ''));
        if ($message === '') {
            throw new \InvalidArgumentException('Le champ "userMessage" est requis.');
        }

        $log = new ChatbotLog();
        $log->setSessionId($this->resolveSessionId((string) ($data['sessionId'] ?? '')));
        $log->setUserMessage($message);
        $log->setBotResponse(isset($data['botResponse']) ? (string) $data['botResponse'] : null);
        $log->setMatchedIntent(isset($data['matchedIntent']) ? (string) $data['matchedIntent'] : null);
        $log->setCategory(isset($data['category']) ? (string) $data['category'] : null);
        $log->setLocale((string) ($data['locale'] ?? 'fr'));

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        return $this->map($log);
    }

    /**
     * Marque une session comme escaladée vers un agent humain.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function escalate(array $data): array
    {
        $sessionId = $this->resolveSessionId((string) ($data['sessionId'] ?? ''));

        $log = new ChatbotLog();
        $log->setSessionId($sessionId);
        $log->setUserMessage(trim((string) ($data['message'] ?? 'Demande de mise en relation avec un agent.')));
        $log->setBotResponse(null);
        $log->setMatchedIntent('escalation');
        $log->setCategory('support');
        $log->setLocale((string) ($data['locale'] ?? 'fr'));
        $log->setEscalated(true);

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        return [
            'sessionId' => $sessionId,
            'escalated' => true,
            'logId' => $log->getId(),
        ];
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, meta: array<string, mixed>}
     */
    public function list(?string $sessionId, ?bool $escalatedOnly, int $page, int $limit): array
    {
        $paginator = $this->chatbotLogRepository->paginate($sessionId, $escalatedOnly, $page, $limit);

        $items = [];
        foreach ($paginator as $log) {
            $items[] = $this->map($log);
        }

        $total = count($paginator);

        return [
            'items' => $items,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int) max(1, (int) ceil($total / max(1, $limit))),
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function conversation(string $sessionId): array
    {
        return array_map(
            fn (ChatbotLog $log): array => $this->map($log),
            $this->chatbotLogRepository->findBySession($sessionId)
        );
    }

    private function resolveSessionId(string $sessionId): string
    {
        $sessionId = trim($sessionId);

        return $sessionId !== '' ? $sessionId : bin2hex(random_bytes(16));
    }

    /**
     * @return array<string, mixed>
     */
    private function map(ChatbotLog $log): array
    {
        return [
            'id' => $log->getId(),
            'sessionId' => $log->getSessionId(),
            'userMessage' => $log->getUserMessage(),
            'botResponse' => $log->getBotResponse(),
            'matchedIntent' => $log->getMatchedIntent(),
            'category' => $log->getCategory(),
            'locale' => $log->getLocale(),
            'escalated' => $log->isEscalated(),
            'createdAt' => $log->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
