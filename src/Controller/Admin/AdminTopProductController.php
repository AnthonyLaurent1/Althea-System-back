<?php

namespace App\Controller\Admin;

use App\Service\Admin\Homepage\TopProductService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/homepage/top-products')]
#[IsGranted('ROLE_ADMIN')]
final class AdminTopProductController extends AbstractController
{
    public function __construct(
        private readonly TopProductService $topProductService,
    ) {
    }

    #[Route('', name: 'api_admin_top_products_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json($this->topProductService->list());
    }

    #[Route('', name: 'api_admin_top_products_replace', methods: ['PUT'])]
    public function replace(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data) || !isset($data['productIds']) || !is_array($data['productIds'])) {
            return $this->json(['error' => 'Le champ "productIds" est requis.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            return $this->json($this->topProductService->replaceSelection(array_map('intval', $data['productIds'])));
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
