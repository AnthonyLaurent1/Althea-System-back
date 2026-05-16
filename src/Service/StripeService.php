<?php

namespace App\Service;

use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class StripeService
{
    public function __construct(
        #[Autowire('%env(STRIPE_SECRET_KEY)%')]
        private string $stripeSecretKey
    ) {
        Stripe::setApiKey($this->stripeSecretKey);
    }

    /**
     * @throws ApiErrorException
     */
    public function createCheckoutSession(
        array $items,
        string $successUrl,
        string $cancelUrl,
        int $orderId
    ): Session {

        $lineItems = [];

        foreach ($items as $item) {
            $lineItems[] = [
                'quantity' => $item['quantity'],
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $item['title'] ?? $item['name'] ?? 'Produit',
                    ],
                    'unit_amount' => (int) ($item['price'] * 100),
                ],
            ];
        }

        return Session::create([
            'payment_method_types' => ['card'],
            'mode' => 'payment',
            'line_items' => $lineItems,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,

            'metadata' => [
                'orderId' => (string) $orderId
            ]
        ]);
    }
}
