<?php

namespace App\Controller;

use App\Service\ChatbotService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/chatbot')]
final class ChatbotController extends AbstractController
{
    public function __construct(
        private readonly ChatbotService $chatbotService,
    ) {
    }

    #[Route('/questions', name: 'api_chatbot_questions', methods: ['GET'])]
    public function questions(): JsonResponse
    {
        return $this->json(['items' => $this->chatbotService->questions()]);
    }

    #[Route('/conversations', name: 'api_chatbot_conversation_start', methods: ['POST'])]
    public function start(Request $request): JsonResponse
    {
        return $this->handle($request, fn (array $data): array => $this->chatbotService->start($data), Response::HTTP_CREATED);
    }

    #[Route('/messages', name: 'api_chatbot_message_create', methods: ['POST'])]
    public function message(Request $request): JsonResponse
    {
        return $this->handle($request, fn (array $data): array => $this->chatbotService->reply($data));
    }

    #[Route('/escalate', name: 'api_chatbot_escalate', methods: ['POST'])]
    public function escalate(Request $request): JsonResponse
    {
        return $this->handle($request, fn (array $data): array => $this->chatbotService->escalate($data), Response::HTTP_CREATED);
    }

    private function handle(Request $request, callable $callback, int $status = Response::HTTP_OK): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'JSON invalide.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            return $this->json($callback($data), $status);
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
