<?php

namespace App\Controller;

use App\Service\Admin\Carousel\CarouselService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/carousel')]
final class CarouselController extends AbstractController
{
    public function __construct(
        private readonly CarouselService $carouselService,
    ) {
    }

    #[Route('', name: 'api_carousel_public', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json($this->carouselService->listActive());
    }
}
