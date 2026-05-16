<?php

namespace App\EventListener;

use App\Entity\Orders;
use App\Entity\Items;
use App\Entity\User;
use App\Repository\OrdersRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\HttpFoundation\RequestStack;

readonly class LoginSuccessListener implements EventSubscriberInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $em,
        private OrdersRepository $ordersRepository,
        private ProductRepository $productRepository
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {


        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        $session = $this->requestStack->getSession();
        $guestCart = $session->get('cart', []);

        if (empty($guestCart)) {
            return;
        }

        $order = $this->ordersRepository->findOneBy([
            'user' => $user,
            'status' => 'cart'
        ]) ?? new Orders();

        if (!$order->getId()) {
            $order->setUser($user)
                ->setStatus('cart')
                ->setPaymentDate(new \DateTime())
                ->setTotalPrice(0);

            $this->em->persist($order);
        }

        foreach ($guestCart as $productId => $qty) {

            $product = $this->productRepository->find($productId);

            if (!$product || $product->getInStock() <= 0) {
                continue;
            }

            $existingItem = null;

            foreach ($order->getItems() as $item) {
                if ($item->getProduct()->getId() === (int)$productId) {
                    $existingItem = $item;
                    break;
                }
            }

            if ($existingItem) {
                $existingItem->setQuantity(
                    $existingItem->getQuantity() + $qty
                );
            } else {
                $item = new Items();
                $item->setProduct($product)
                    ->setQuantity($qty)
                    ->setPrice($product->getPrice())
                    ->setOrders($order);

                $order->getItems()->add($item);
                $this->em->persist($item);
            }
        }

        $session->remove('cart');

        $total = 0;
        foreach ($order->getItems() as $item) {
            $total += $item->getPrice() * $item->getQuantity();
        }

        $order->setTotalPrice($total);

        $this->em->flush();
    }
}
