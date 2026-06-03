<?php

namespace App\Controller;

use App\Service\ChatbotLogService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/chatbot')]
final class ChatbotController extends AbstractController
{
    public function __construct(
        private readonly ChatbotLogService $chatbotLogService,
    ) {
    }

    #[Route('/message', name: 'api_chatbot_message', methods: ['POST'])]
    public function message(Request $request): JsonResponse
    {
        $data = $this->decode($request);

        try {
            return $this->json($this->chatbotLogService->handleMessage(
                (string) ($data['sessionId'] ?? ''),
                (string) ($data['message'] ?? ''),
                (string) ($data['locale'] ?? 'fr'),
            ));
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/log', name: 'api_chatbot_log', methods: ['POST'])]
    public function log(Request $request): JsonResponse
    {
        try {
            return $this->json($this->chatbotLogService->log($this->decode($request)), Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/escalate', name: 'api_chatbot_escalate', methods: ['POST'])]
    public function escalate(Request $request): JsonResponse
    {
        return $this->json($this->chatbotLogService->escalate($this->decode($request)), Response::HTTP_CREATED);
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(Request $request): array
    {
        $data = json_decode($request->getContent(), true);

        return is_array($data) ? $data : [];
    }
}
