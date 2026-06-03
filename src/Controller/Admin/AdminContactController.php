<?php

namespace App\Controller\Admin;

use App\Entity\ContactRequest;
use App\Service\ContactService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/contact/messages')]
#[IsGranted('ROLE_ADMIN')]
final class AdminContactController extends AbstractController
{
    public function __construct(
        private readonly ContactService $contactService,
    ) {
    }

    #[Route('', name: 'api_admin_contact_index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        return $this->json($this->contactService->list(
            $request->query->get('status'),
            max(1, $request->query->getInt('page', 1)),
            max(1, min(100, $request->query->getInt('limit', 20))),
        ));
    }

    #[Route('/{id}', name: 'api_admin_contact_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(ContactRequest $contactRequest): JsonResponse
    {
        return $this->json($this->contactService->markAsRead($contactRequest));
    }

    #[Route('/{id}/status', name: 'api_admin_contact_status', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function status(ContactRequest $contactRequest, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data) || !isset($data['status'])) {
            return $this->json(['error' => 'Le champ "status" est requis.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            return $this->json($this->contactService->changeStatus($contactRequest, (string) $data['status']));
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}/reply', name: 'api_admin_contact_reply', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function reply(ContactRequest $contactRequest, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data) || !isset($data['reply'])) {
            return $this->json(['error' => 'Le champ "reply" est requis.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            return $this->json($this->contactService->reply($contactRequest, (string) $data['reply']));
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
