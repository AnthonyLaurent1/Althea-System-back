<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\OrdersRepository;
use App\Service\InvoicePdfService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/invoice')]
class InvoiceController extends AbstractController
{
    #[Route('/{id}', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function invoice(int $id, OrdersRepository $ordersRepository, InvoicePdfService $invoicePdfService): Response {
        $user = $this->getRealUser();

        if (!$user) {
            return $this->json(['error' => 'Utilisateur invalide'], 401);
        }

        $order = $ordersRepository->find($id);

        if (!$order) {
            return $this->json(['error' => 'Commande introuvable'], 404);
        }

        if ($order->getUser()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        if ($order->getStatus() !== 'Payé') {
            return $this->json(['error' => 'Facture disponible uniquement pour une commande payée'], 400);
        }

        $items = [];
        $subtotal = 0;

        foreach ($order->getItems() as $item) {
            $unitPrice = (float) $item->getPrice();
            $quantity = $item->getQuantity();
            $lineTotal = $unitPrice * $quantity;

            $items[] = [
                'title' => $item->getProduct()->getTitle(),
                'quantity' => $quantity,
                'unitPrice' => $unitPrice,
                'lineTotal' => $lineTotal,
            ];

            $subtotal += $lineTotal;
        }

        $promo = max(0, round(($subtotal - $order->getTotalPrice()) * 100) / 100);
        $total = (float) $order->getTotalPrice();

        $html = $this->renderView('pdf.html.twig', [
            'order' => $order,
            'customer' => $user,
            'items' => $items,
            'subtotal' => $subtotal,
            'promo' => $promo,
            'total' => $total,
            'invoiceNumber' => sprintf('FAC-%s-%06d', $order->getPaymentDate()?->format('Y'), $order->getId()),
        ]);

        $pdf = $invoicePdfService->generateFromHtml($html);

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename=\"facture-%d.pdf\"', $order->getId()),
        ]);
    }

    private function getRealUser(): ?User
    {
        $user = $this->getUser();

        return $user instanceof User ? $user : null;
    }
}
