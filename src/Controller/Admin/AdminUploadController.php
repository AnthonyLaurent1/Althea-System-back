<?php

namespace App\Controller\Admin;

use App\Service\ImageUploadService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/upload')]
#[IsGranted('ROLE_ADMIN')]
final class AdminUploadController extends AbstractController
{
    public function __construct(
        private readonly ImageUploadService $imageUploadService,
    ) {
    }

    #[Route('', name: 'api_admin_upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        try {
            return $this->json($this->imageUploadService->upload($request->files->get('file')), Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
