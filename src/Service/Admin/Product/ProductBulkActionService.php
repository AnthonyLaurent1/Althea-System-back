<?php

namespace App\Service\Admin\Product;

use App\Dto\Admin\Product\BulkProductActionDto;
use App\Entity\Discount;
use App\Entity\Product;
use App\Exception\InvalidBulkActionException;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ProductBulkActionService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProductRepository $productRepository,
        private readonly AdminProductService $adminProductService,
    ) {
    }

    public function execute(BulkProductActionDto $dto): array
    {
        if ($dto->productIds === []) {
            throw new InvalidBulkActionException('Aucun produit sélectionné.');
        }

        $products = $this->productRepository->findBy(['id' => $dto->productIds]);
        $indexed = [];
        foreach ($products as $product) {
            $indexed[(int) $product->getId()] = $product;
        }

        $missingIds = array_values(array_diff($dto->productIds, array_keys($indexed)));
        $affected = [];

        foreach ($indexed as $product) {
            $affected[] = $this->applyAction($product, $dto);
        }

        $this->entityManager->flush();

        return [
            'action' => $dto->action,
            'affectedIds' => array_map(static fn (Product $product) => (int) $product->getId(), $indexed),
            'missingIds' => $missingIds,
            'count' => count($indexed),
            'details' => $affected,
        ];
    }

    private function applyAction(Product $product, BulkProductActionDto $dto): array
    {
        return match ($dto->action) {
            'delete' => $this->delete($product),
            'publish' => $this->setPublished($product, true),
            'unpublish' => $this->setPublished($product, false),
            'promote' => $this->promote($product, $dto),
            default => throw new InvalidBulkActionException('Action groupée inconnue.'),
        };
    }

    private function delete(Product $product): array
    {
        $this->adminProductService->delete($product, false);

        return [
            'id' => (int) $product->getId(),
            'status' => 'deleted',
        ];
    }

    private function setPublished(Product $product, bool $isPublished): array
    {
        $product->setIsPublished($isPublished);

        return [
            'id' => (int) $product->getId(),
            'status' => $isPublished ? 'published' : 'unpublished',
        ];
    }

    private function promote(Product $product, BulkProductActionDto $dto): array
    {
        if ($dto->discountPercentage === null || $dto->discountPercentage < 1 || $dto->discountPercentage > 100) {
            throw new InvalidBulkActionException('Le pourcentage de promotion est invalide.');
        }

        try {
            $startDate = new \DateTimeImmutable($dto->discountStartDate ?? 'today');
            $endDate = new \DateTimeImmutable($dto->discountEndDate ?? '+7 days');
        } catch (\Exception $exception) {
            throw new InvalidBulkActionException('Les dates de promotion sont invalides.', previous: $exception);
        }

        if ($endDate < $startDate) {
            throw new InvalidBulkActionException('La date de fin doit être postérieure à la date de début.');
        }

        $discount = new Discount();
        $discount->setProduct($product)
            ->setPercentage($dto->discountPercentage)
            ->setStartDate($startDate)
            ->setEndDate($endDate);

        $this->entityManager->persist($discount);

        return [
            'id' => (int) $product->getId(),
            'status' => 'promoted',
            'percentage' => $dto->discountPercentage,
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
        ];
    }
}
