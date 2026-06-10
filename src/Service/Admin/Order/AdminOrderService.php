<?php

namespace App\Service\Admin\Order;

use App\Entity\Orders;
use App\Enum\OrderStatus;
use App\Repository\OrdersRepository;
use Doctrine\ORM\EntityManagerInterface;

final class AdminOrderService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrdersRepository $ordersRepository,
    ) {
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, meta: array<string, mixed>}
     */
    public function list(
        ?string $status,
        ?int $userId,
        ?string $from,
        ?string $to,
        string $sortBy,
        string $sortDirection,
        int $page,
        int $limit,
    ): array {
        $paginator = $this->ordersRepository->paginateAdminList($status, $userId, $from, $to, $sortBy, $sortDirection, $page, $limit);

        $items = [];
        foreach ($paginator as $order) {
            $items[] = $this->mapSummary($order);
        }

        $total = count($paginator);

        return [
            'items' => $items,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int) max(1, (int) ceil($total / max(1, $limit))),
                'sortBy' => $sortBy,
                'sortDirection' => strtoupper($sortDirection) === 'ASC' ? 'ASC' : 'DESC',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(Orders $order): array
    {
        return $this->mapDetail($order);
    }

    /**
     * @return array<string, mixed>
     */
    public function updateStatus(Orders $order, string $status): array
    {
        if (!in_array($status, OrderStatus::adminEditableValues(), true)) {
            throw new \InvalidArgumentException(sprintf(
                'Statut invalide. Valeurs autorisées : %s.',
                implode(', ', OrderStatus::adminEditableValues())
            ));
        }

        $order->setStatus($status);
        $this->entityManager->flush();

        return $this->mapDetail($order);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapSummary(Orders $order): array
    {
        $user = $order->getUser();

        return [
            'id' => $order->getId(),
            'status' => $order->getStatus(),
            'totalPrice' => $order->getTotalPrice(),
            'paymentDate' => $order->getPaymentDate()?->format('Y-m-d'),
            'itemsCount' => $order->getItems()->count(),
            'customer' => $user === null ? null : [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapDetail(Orders $order): array
    {
        $summary = $this->mapSummary($order);

        $items = [];
        foreach ($order->getItems() as $item) {
            $product = $item->getProduct();
            $items[] = [
                'id' => $item->getId(),
                'productId' => $product?->getId(),
                'productTitle' => $product?->getTitle(),
                'quantity' => $item->getQuantity(),
                'unitPrice' => $item->getPrice(),
                'lineTotal' => round((float) $item->getPrice() * (int) $item->getQuantity(), 2),
            ];
        }

        $user = $order->getUser();
        $summary['items'] = $items;
        $summary['billing'] = $user === null ? null : [
            'address' => $user->getAddress(),
            'additionalAddress' => $user->getAdditionalAddress(),
            'postalCode' => $user->getPostalCode(),
            'city' => $user->getCity(),
            'country' => $user->getCountry(),
            'phone' => $user->getPhone(),
            'company' => $user->getCompany(),
        ];

        return $summary;
    }
}
