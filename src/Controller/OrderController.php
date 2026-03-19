<?php

namespace App\Controller;

use App\Dto\AddItemDto;
use App\Dto\UpdateItemQuantityDto;
use App\Entity\Orders;
use App\Entity\Items;
use App\Repository\OrdersRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/order')]
class OrderController extends AbstractController
{
    #[Route('/add-item', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function addItem(
        Request $request,
        EntityManagerInterface $em,
        OrdersRepository $orderRepository,
        ProductRepository $productRepository
    ): JsonResponse {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        $dto = new AddItemDto($data['productId'] ?? 0, $data['quantity'] ?? 1);

        if ($dto->productId <= 0 || $dto->quantity <= 0) {
            return $this->json(['error' => 'Données invalides'], 400);
        }

        $order = $orderRepository->findOneBy(['user' => $user, 'status' => 'cart']) ?? new Orders();
        if (!$order->getId()) {
            $order->setUser($user)
                ->setStatus('cart')
                ->setPaymentDate(new \DateTime())
                ->setTotalPrice(0);
            $em->persist($order);
        }

        $product = $productRepository->find($dto->productId);
        if (!$product) return $this->json(['error' => 'Produit introuvable'], 404);
        if ($product->getInStock() < $dto->quantity) return $this->json(['error' => 'Stock insuffisant'], 400);

        $existingItem = null;
        foreach ($order->getItems() as $item) {
            if ($item->getProduct()->getId() === $dto->productId) {
                $existingItem = $item;
                break;
            }
        }

        if ($existingItem) {
            $newQty = $existingItem->getQuantity() + $dto->quantity;
            if ($newQty > $product->getInStock()) return $this->json(['error' => 'Stock insuffisant'], 400);
            $existingItem->setQuantity($newQty);
        } else {
            $item = new Items();
            $item->setProduct($product)
                ->setQuantity($dto->quantity)
                ->setPrice($product->getPrice())
                ->setOrders($order);

            $order->getItems()->add($item);

            $em->persist($item);
        }

        $total = 0;
        foreach ($order->getItems() as $item) {
            $total += $item->getPrice() * $item->getQuantity();
        }
        $order->setTotalPrice($total);

        $em->flush();

        return $this->json(['message' => 'Item ajouté', 'totalPrice' => $total]);
    }

    #[Route('/my-order', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getMyCart(OrdersRepository $orderRepository): JsonResponse
    {
        $user = $this->getUser();

        $order = $orderRepository->findOneBy([
            'user' => $user,
            'status' => 'cart'
        ]);

        if (!$order) {
            return $this->json(['message' => 'Panier vide'], 404);
        }

        $items = [];

        foreach ($order->getItems() as $item) {
            $items[] = [
                'itemId' => $item->getId(),
                'productId' => $item->getProduct()->getId(),
                'title' => $item->getProduct()->getTitle(),
                'quantity' => $item->getQuantity(),
                'price' => $item->getPrice(),
                'total' => $item->getPrice() * $item->getQuantity(),
            ];
        }

        return $this->json([
            'orderId' => $order->getId(),
            'userId' => $order->getUser()->getId(),
            'status' => $order->getStatus(),
            'totalPrice' => $order->getTotalPrice(),
            'items' => $items
        ]);
    }

    #[Route('/update-items', methods: ['PATCH'])]
    #[IsGranted('ROLE_USER')]
    public function updateItemsQuantity(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);
        $dto = new UpdateItemQuantityDto($data['items'] ?? []);

        $order = $em->getRepository(Orders::class)->findOneBy([
            'user' => $user,
            'status' => 'cart'
        ]);

        if (!$order) {
            return $this->json(['error' => 'Panier vide'], 404);
        }

        $total = 0;

        foreach ($dto->items as $update) {
            $item = $em->getRepository(Items::class)->find($update['itemId'] ?? 0);
            if (!$item || $item->getOrders()->getId() !== $order->getId()) continue;

            $newQty = max(1, (int)$update['quantity']); // au moins 1
            $productStock = $item->getProduct()->getInStock();

            if ($newQty > $productStock) {
                return $this->json([
                    'error' => "Stock insuffisant pour le produit {$item->getProduct()->getTitle()}"
                ], 400);
            }

            $item->setQuantity($newQty);
            $total += $item->getPrice() * $newQty;
        }

        $order->setTotalPrice($total);

        $em->flush();

        return $this->json([
            'message' => 'Quantités mises à jour',
            'totalPrice' => $total
        ]);
    }
}
