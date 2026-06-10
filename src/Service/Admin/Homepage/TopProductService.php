<?php

namespace App\Service\Admin\Homepage;

use App\Entity\TopProduct;
use App\Repository\ProductRepository;
use App\Repository\TopProductRepository;
use Doctrine\ORM\EntityManagerInterface;

final class TopProductService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TopProductRepository $topProductRepository,
        private readonly ProductRepository $productRepository,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(): array
    {
        return array_map(
            fn (TopProduct $top): array => $this->map($top),
            $this->topProductRepository->findAllOrdered()
        );
    }

    /**
     * Remplace toute la sélection par la liste d'IDs fournie (ordre = position).
     *
     * @param int[] $productIds
     *
     * @return array<int, array<string, mixed>>
     */
    public function replaceSelection(array $productIds): array
    {
        foreach ($this->topProductRepository->findAll() as $existing) {
            $this->entityManager->remove($existing);
        }
        $this->entityManager->flush();

        $position = 0;
        foreach ($productIds as $productId) {
            $product = $this->productRepository->find((int) $productId);
            if ($product === null) {
                throw new \InvalidArgumentException(sprintf('Produit %d introuvable.', (int) $productId));
            }

            $top = new TopProduct();
            $top->setProduct($product);
            $top->setPosition($position);
            $this->entityManager->persist($top);
            ++$position;
        }

        $this->entityManager->flush();

        return $this->list();
    }

    /**
     * @return array<string, mixed>
     */
    private function map(TopProduct $top): array
    {
        $product = $top->getProduct();

        return [
            'position' => $top->getPosition(),
            'product' => $product === null ? null : [
                'id' => $product->getId(),
                'title' => $product->getTitle(),
                'price' => $product->getPrice(),
                'pictureUrl' => $product->getPictureUrl(),
                'inStock' => $product->getInStock(),
                'isPublished' => $product->isPublished(),
            ],
        ];
    }
}
