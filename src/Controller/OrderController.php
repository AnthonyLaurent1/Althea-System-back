<?php

namespace App\Controller;

use App\Dto\AddItemDto;
use App\Dto\UpdateItemQuantityDto;
use App\Entity\Orders;
use App\Entity\Items;
use App\Entity\User;
use App\Repository\OrdersRepository;
use App\Repository\ProductRepository;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Stripe\Exception\ApiErrorException;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/order')]
class OrderController extends AbstractController
{
    private function calculateTotal(Orders $order): array
    {
        $subtotal = 0;

        foreach ($order->getItems() as $item) {
            $subtotal += (int) round($item->getPrice() * 100) * $item->getQuantity();        }

        $promo = $subtotal > 100 ? 10 : 0;

        $total = $subtotal - $promo;

        return [
            'subtotal' => round($subtotal, 2),
            'promo' => $promo,
            'total' => $total / 100
        ];
    }

    #[Route('/add-item', methods: ['POST'])]
    public function addItem(
        Request $request,
        EntityManagerInterface $em,
        OrdersRepository $orderRepository,
        ProductRepository $productRepository
    ): JsonResponse {
        $session = $request->getSession();
        $user = $this->getRealUser();

        $data = json_decode($request->getContent(), true);
        $dto = new AddItemDto($data['productId'] ?? 0, $data['quantity'] ?? 1);

        if ($dto->productId <= 0 || $dto->quantity <= 0) {
            return $this->json(['error' => 'Données invalides'], 400);
        }

        $product = $productRepository->find($dto->productId);

        if (!$product) {
            return $this->json(['error' => 'Produit introuvable'], 404);
        }

        if ($product->getInStock() <= 0) {
            return $this->json(['error' => 'Produit indisponible'], 400);
        }

        if (!$user) {

            $guestCart = $session->get('cart', []);

            $currentQty = $guestCart[$dto->productId] ?? 0;
            $newQty = $currentQty + $dto->quantity;

            if ($newQty > $product->getInStock()) {
                return $this->json(['error' => 'Stock insuffisant'], 400);
            }

            $guestCart[$dto->productId] = $newQty;
            $session->set('cart', $guestCart);

            return $this->json([
                'message' => 'Item ajouté au panier invité',
                'cart' => $guestCart
            ]);
        }

        $order = $orderRepository->findOneBy([
            'user' => $user,
            'status' => 'cart'
        ]) ?? new Orders();

        if (!$order->getId()) {
            $order->setUser($user)
                ->setStatus('cart')
                ->setPaymentDate(new \DateTime())
                ->setTotalPrice(0);

            $em->persist($order);
        }

        $existingItem = null;

        foreach ($order->getItems() as $item) {
            if ($item->getProduct()->getId() === $dto->productId) {
                $existingItem = $item;
                break;
            }
        }

        if ($existingItem) {

            $newQty = $existingItem->getQuantity() + $dto->quantity;

            if ($newQty > $product->getInStock()) {
                return $this->json(['error' => 'Stock insuffisant'], 400);
            }

            $existingItem->setQuantity($newQty);

        } else {

            if ($dto->quantity > $product->getInStock()) {
                return $this->json(['error' => 'Stock insuffisant'], 400);
            }

            $item = new Items();
            $item->setProduct($product)
                ->setQuantity($dto->quantity)
                ->setPrice($product->getPrice())
                ->setOrders($order);

            $order->getItems()->add($item);
            $em->persist($item);
        }

        $totals = $this->calculateTotal($order);
        $order->setTotalPrice($totals['total']);

        $em->flush();

        return $this->json([
            'message' => 'Item ajouté',
            'totalPrice' => $totals['total'],
            'promo' => $totals['promo']
        ]);
    }

    #[Route('/my-order', methods: ['GET'])]
    public function getMyCart(Request $request, OrdersRepository $orderRepository): JsonResponse
    {
        $user = $this->getRealUser();

        if (!$user) {
            $session = $request->getSession();
            $guestCart = $session->get('cart', []);

            return $this->json([
                'orderId' => null,
                'items' => $guestCart,
                'totalPrice' => 0
            ]);
        }


        $order = $orderRepository->findOneBy([
            'user' => $user,
            'status' => 'cart'
        ]);

        if (!$order) {
            return $this->json([
                'orderId' => null,
                'items' => [],
                'totalPrice' => 0
            ]);
        }

        $items = [];

        foreach ($order->getItems() as $item) {
            $product = $item->getProduct();

            $items[] = [
                'itemId' => $item->getId(),
                'productId' => $product->getId(),
                'title' => $product->getTitle(),
                'quantity' => $item->getQuantity(),
                'price' => $item->getPrice(),
                'total' => $item->getPrice() * $item->getQuantity(),
                'available' => $product->getInStock() > 0
            ];
        }

        $totals = $this->calculateTotal($order);

        return $this->json([
            'orderId' => $order->getId(),
            'userId' => $order->getUser()->getId(),
            'status' => $order->getStatus(),
            'totalPrice' => $totals['total'],
            'promo' => $totals['promo'],
            'items' => $items
        ]);
    }

    #[Route('/update-items', methods: ['PATCH'])]
    #[IsGranted('ROLE_USER')]
    public function updateItemsQuantity(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getRealUser();

        if (!$user) {
            return $this->json(['error' => 'Utilisateur invalide'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $dto = new UpdateItemQuantityDto($data['items'] ?? []);

        $order = $em->getRepository(Orders::class)->findOneBy([
            'user' => $user,
            'status' => 'cart'
        ]);

        if (!$order) {
            return $this->json(['items' => [], 'totalPrice' => 0]);
        }

        foreach ($dto->items as $update) {
            $item = $em->getRepository(Items::class)->find($update['itemId'] ?? 0);

            if (!$item || $item->getOrders()->getId() !== $order->getId()) {
                continue;
            }

            $newQty = max(1, (int) $update['quantity']);
            $productStock = $item->getProduct()->getInStock();

            if ($productStock <= 0) {
                return $this->json([
                    'error' => "Produit indisponible : {$item->getProduct()->getTitle()}"
                ], 400);
            }

            if ($newQty > $productStock) {
                return $this->json([
                    'error' => "Stock insuffisant pour {$item->getProduct()->getTitle()}"
                ], 400);
            }

            $item->setQuantity($newQty);
        }

        $totals = $this->calculateTotal($order);
        $order->setTotalPrice($totals['total']);

        $em->flush();

        return $this->json([
            'message' => 'Quantités mises à jour',
            'totalPrice' => $totals['total'],
            'promo' => $totals['promo']
        ]);
    }

    #[Route('/remove-item/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function removeItem(int $id, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getRealUser();

        if (!$user) {
            return $this->json(['error' => 'Utilisateur invalide'], 401);
        }

        $order = $em->getRepository(Orders::class)->findOneBy([
            'user' => $user,
            'status' => 'cart'
        ]);

        if (!$order) {
            return $this->json(['items' => [], 'totalPrice' => 0]);
        }

        $item = $em->getRepository(Items::class)->find($id);

        if (!$item || $item->getOrders()->getId() !== $order->getId()) {
            return $this->json(['error' => 'Item introuvable'], 404);
        }

        $em->remove($item);
        $order->getItems()->removeElement($item);

        $totals = $this->calculateTotal($order);
        $order->setTotalPrice($totals['total']);

        $em->flush();

        return $this->json([
            'message' => 'Item supprimé',
            'totalPrice' => $totals['total']
        ]);
    }

    /**
     * @throws ApiErrorException
     */
    #[Route('/checkout', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function checkout(EntityManagerInterface $em, StripeService $stripeService): JsonResponse
    {
        $user = $this->getRealUser();

        if (!$user) {
            return $this->json(['error' => 'Utilisateur invalide'], 401);
        }

        $order = $em->getRepository(Orders::class)->findOneBy([
            'user' => $user,
            'status' => 'cart'
        ]);

        if (!$order || count($order->getItems()) === 0) {
            return $this->json(['error' => 'Panier vide'], 400);
        }

        foreach ($order->getItems() as $item) {
            $product = $item->getProduct();

            if ($product->getInStock() <= 0) {
                return $this->json([
                    'error' => "Produit indisponible : {$product->getTitle()}"
                ], 400);
            }

            if ($product->getInStock() < $item->getQuantity()) {
                return $this->json([
                    'error' => "Stock insuffisant pour {$product->getTitle()}"
                ], 400);
            }
        }

        $totals = $this->calculateTotal($order);
        $order->setTotalPrice($totals['total']);

        return $this->createStripeSession($order, $stripeService);
    }
    private function getRealUser(): ?User
    {
        $user = $this->getUser();

        return $user instanceof User ? $user : null;
    }

    /**
     * @throws ApiErrorException
     */
    private function createStripeSession(Orders $order, StripeService $stripeService): JsonResponse
    {
        $items = [];

        foreach ($order->getItems() as $item) {
            $items[] = [
                'name' => $item->getProduct()->getTitle(),
                'price' => $item->getPrice(),
                'quantity' => $item->getQuantity(),
            ];
        }

        $session = $stripeService->createCheckoutSession(
            $items,
            'http://localhost:8000/api/order/success?session_id={CHECKOUT_SESSION_ID}',
            'http://localhost:3000/cancel',
            $order->getId()
        );

        return $this->json([
            'message' => 'Redirection vers Stripe',
            'orderId' => $order->getId(),
            'url' => $session->url
        ]);
    }

    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function stripeWebhook(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $_ENV['STRIPE_WEBHOOK_SECRET']
            );
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'invalid webhook'], 400);
        }

        if ($event->type !== 'checkout.session.completed') {
            return new JsonResponse(['ignored' => true]);
        }

        $session = $event->data->object;
        $orderId = $session->metadata->orderId ?? null;

        if (!$orderId) {
            return new JsonResponse(['error' => 'missing orderId'], 400);
        }

        $order = $em->getRepository(Orders::class)->find($orderId);

        if (!$order) {
            return new JsonResponse(['error' => 'order not found'], 404);
        }

        if ($order->getStatus() === 'paid') {
            return new JsonResponse(['message' => 'already processed']);
        }

        foreach ($order->getItems() as $item) {
            $product = $item->getProduct();

            $newStock = $product->getInStock() - $item->getQuantity();

            if ($newStock < 0) {
                return new JsonResponse([
                    'error' => "Stock insuffisant pour {$product->getTitle()}"
                ], 400);
            }

            $product->setInStock($newStock);
        }

        $order->setStatus('paid');
        $order->setPaymentDate(new \DateTime());

        $em->flush();

        return new JsonResponse(['ok' => true]);
    }

    #[Route('/success', methods: ['GET'])]
    public function success(Request $request): JsonResponse
    {
        $sessionId = $request->query->get('session_id');

        return $this->json([
            'message' => 'Paiement réussi',
            'session_id' => $sessionId
        ]);
    }
}
