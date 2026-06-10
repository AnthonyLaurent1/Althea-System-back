<?php

namespace App\Controller\Admin;

use App\Service\AdminTwoFactorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/auth')]
final class AdminAuthController extends AbstractController
{
    public function __construct(
        private readonly AdminTwoFactorService $adminTwoFactorService,
    ) {
    }

    #[Route('/verify-2fa', name: 'api_admin_auth_verify_2fa', methods: ['POST'])]
    public function verify2fa(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'JSON invalide.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            return $this->json($this->adminTwoFactorService->verify(
                (string) ($data['challengeId'] ?? ''),
                (string) ($data['code'] ?? ''),
            ));
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_UNAUTHORIZED);
        }
    }
}
