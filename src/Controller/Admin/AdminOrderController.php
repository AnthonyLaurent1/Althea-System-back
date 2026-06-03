<?php

namespace App\Controller\Admin;

use App\Entity\Orders;
use App\Service\Admin\Order\AdminOrderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/orders')]
#[IsGranted('ROLE_ADMIN')]
final class AdminOrderController extends AbstractController
{
    public function __construct(
        private readonly AdminOrderService $adminOrderService,
    ) {
    }

    #[Route('', name: 'api_admin_orders_index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        return $this->json($this->adminOrderService->list(
            $request->query->get('status'),
            $request->query->has('userId') ? $request->query->getInt('userId') : null,
            $request->query->get('from'),
            $request->query->get('to'),
            (string) $request->query->get('sortBy', 'id'),
            (string) $request->query->get('sortDirection', 'DESC'),
            max(1, $request->query->getInt('page', 1)),
            max(1, min(100, $request->query->getInt('limit', 20))),
        ));
    }

    #[Route('/{id}', name: 'api_admin_orders_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Orders $order): JsonResponse
    {
        return $this->json($this->adminOrderService->detail($order));
    }

    #[Route('/{id}/status', name: 'api_admin_orders_status', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function updateStatus(Orders $order, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data) || !isset($data['status'])) {
            return $this->json(['error' => 'Le champ "status" est requis.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            return $this->json($this->adminOrderService->updateStatus($order, (string) $data['status']));
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
