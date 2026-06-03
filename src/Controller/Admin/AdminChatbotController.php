<?php

namespace App\Controller\Admin;

use App\Service\ChatbotLogService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/chatbot')]
#[IsGranted('ROLE_ADMIN')]
final class AdminChatbotController extends AbstractController
{
    public function __construct(
        private readonly ChatbotLogService $chatbotLogService,
    ) {
    }

    #[Route('/logs', name: 'api_admin_chatbot_logs', methods: ['GET'])]
    public function logs(Request $request): JsonResponse
    {
        return $this->json($this->chatbotLogService->list(
            $request->query->get('sessionId'),
            $request->query->has('escalatedOnly')
                ? filter_var($request->query->get('escalatedOnly'), FILTER_VALIDATE_BOOL)
                : null,
            max(1, $request->query->getInt('page', 1)),
            max(1, min(100, $request->query->getInt('limit', 20))),
        ));
    }

    #[Route('/logs/{sessionId}', name: 'api_admin_chatbot_session', methods: ['GET'])]
    public function session(string $sessionId): JsonResponse
    {
        return $this->json($this->chatbotLogService->conversation($sessionId));
    }
}
