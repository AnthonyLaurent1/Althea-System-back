<?php

namespace App\Controller\Admin;

use App\Entity\ChatbotConversation;
use App\Service\ChatbotService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/chatbot/conversations')]
#[IsGranted('ROLE_ADMIN')]
final class AdminChatbotController extends AbstractController
{
    public function __construct(
        private readonly ChatbotService $chatbotService,
    ) {
    }

    #[Route('', name: 'api_admin_chatbot_conversations_index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        return $this->json($this->chatbotService->list(
            $request->query->get('status'),
            max(1, $request->query->getInt('page', 1)),
            max(1, min(100, $request->query->getInt('limit', 20))),
        ));
    }

    #[Route('/{id}', name: 'api_admin_chatbot_conversations_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(ChatbotConversation $conversation): JsonResponse
    {
        return $this->json($this->chatbotService->show($conversation));
    }
}
