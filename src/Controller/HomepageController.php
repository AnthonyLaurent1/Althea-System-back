<?php

namespace App\Controller;

use App\Service\Admin\Homepage\TopProductService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/homepage')]
final class HomepageController extends AbstractController
{
    public function __construct(
        private readonly TopProductService $topProductService,
    ) {
    }

    #[Route('/top-products', name: 'api_homepage_top_products', methods: ['GET'])]
    public function topProducts(): JsonResponse
    {
        return $this->json($this->topProductService->list());
    }
}
