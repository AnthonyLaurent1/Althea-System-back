<?php

namespace App\Controller\Admin;

use App\Entity\CarouselItem;
use App\Service\Admin\Carousel\CarouselService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/carousel')]
#[IsGranted('ROLE_ADMIN')]
final class AdminCarouselController extends AbstractController
{
    public function __construct(
        private readonly CarouselService $carouselService,
    ) {
    }

    #[Route('', name: 'api_admin_carousel_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json($this->carouselService->listAll());
    }

    #[Route('', name: 'api_admin_carousel_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $this->decode($request);

        try {
            return $this->json($this->carouselService->create($data), Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/reorder', name: 'api_admin_carousel_reorder', methods: ['PATCH'])]
    public function reorder(Request $request): JsonResponse
    {
        $data = $this->decode($request);
        if (!isset($data['orderedIds']) || !is_array($data['orderedIds'])) {
            return $this->json(['error' => 'Le champ "orderedIds" est requis.'], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($this->carouselService->reorder(array_map('intval', $data['orderedIds'])));
    }

    #[Route('/{id}', name: 'api_admin_carousel_update', requirements: ['id' => '\d+'], methods: ['PATCH', 'PUT'])]
    public function update(CarouselItem $carouselItem, Request $request): JsonResponse
    {
        $data = $this->decode($request);

        try {
            return $this->json($this->carouselService->update($carouselItem, $data));
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}', name: 'api_admin_carousel_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(CarouselItem $carouselItem): JsonResponse
    {
        $this->carouselService->delete($carouselItem);

        return $this->json(['message' => 'Élément de carrousel supprimé']);
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
