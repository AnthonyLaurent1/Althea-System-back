<?php

namespace App\Service\Admin\Product;

use App\Dto\Admin\Product\AdminProductDetailDto;
use App\Dto\Admin\Product\AdminProductListItemDto;
use App\Dto\Admin\Product\AdminProductListQueryDto;
use App\Dto\Admin\Product\CreateProductDto;
use App\Dto\Admin\Product\ProductUpsertResponseDto;
use App\Dto\Admin\Product\UpdateProductDto;
use App\Entity\Discount;
use App\Entity\Product;
use App\Entity\ProductTranslation;
use App\Exception\ProductDeletionNotAllowedException;
use App\Repository\CategoryRepository;
use App\Repository\ItemsRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

final class AdminProductService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProductRepository $productRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly ItemsRepository $itemsRepository,
    ) {
    }

    public function list(AdminProductListQueryDto $query): array
    {
        $paginator = $this->productRepository->paginateAdminList(
            $query->search,
            $query->categoryId,
            $query->isPublished,
            $query->sortBy,
            $query->sortDirection,
            $query->page,
            $query->limit
        );

        $items = [];
        foreach ($paginator as $product) {
            $items[] = $this->mapListItem($product);
        }

        $total = count($paginator);

        return [
            'items' => $items,
            'meta' => [
                'page' => $query->page,
                'limit' => $query->limit,
                'total' => $total,
                'pages' => (int) max(1, (int) ceil($total / max(1, $query->limit))),
                'sortBy' => $query->sortBy,
                'sortDirection' => strtoupper($query->sortDirection) === 'ASC' ? 'ASC' : 'DESC',
            ],
        ];
    }

    public function detail(Product $product): AdminProductDetailDto
    {
        return $this->mapDetail($product);
    }

    public function create(CreateProductDto $dto): ProductUpsertResponseDto
    {
        $product = new Product();
        $this->hydrateProduct($product, $dto->title, $dto->description, $dto->price, $dto->pictureUrl, $dto->categoryId, $dto->isPublished, $dto->powerSupplyType, $dto->medicalDomain, $dto->isPortable, $dto->isOneTimeUse, $dto->inStock);

        $this->entityManager->persist($product);
        $this->syncProductTranslations($product, $dto->translations);
        $this->entityManager->flush();

        return new ProductUpsertResponseDto('Produit créé', $this->mapDetail($product));
    }

    public function update(Product $product, UpdateProductDto $dto): ProductUpsertResponseDto
    {
        $this->hydrateProduct(
            $product,
            $dto->title ?? $product->getTitle(),
            $dto->description ?? $product->getDescription(),
            $dto->price ?? $product->getPrice(),
            $dto->pictureUrl ?? $product->getPictureUrl(),
            $dto->categoryId,
            $dto->isPublished ?? $product->isPublished(),
            $dto->powerSupplyType ?? $product->getPowerSupplyType(),
            $dto->medicalDomain ?? $product->getMedicalDomain(),
            $dto->isPortable ?? $product->isPortable(),
            $dto->isOneTimeUse ?? $product->isOneTimeUse(),
            $dto->inStock ?? $product->getInStock()
        );

        if ($dto->translations !== null) {
            $this->syncProductTranslations($product, $dto->translations);
        }

        $this->entityManager->flush();

        return new ProductUpsertResponseDto('Produit modifié', $this->mapDetail($product));
    }

    public function delete(Product $product, bool $flush = true): void
    {
        if ($this->itemsRepository->count(['product' => $product]) > 0) {
            throw new ProductDeletionNotAllowedException('Impossible de supprimer un produit déjà présent dans des commandes.');
        }

        foreach ($product->getDiscounts() as $discount) {
            $this->entityManager->remove($discount);
        }

        $this->entityManager->remove($product);

        if ($flush) {
            $this->entityManager->flush();
        }
    }

    private function hydrateProduct(
        Product $product,
        string $title,
        string $description,
        string $price,
        string $pictureUrl,
        ?int $categoryId,
        bool $isPublished,
        string $powerSupplyType,
        string $medicalDomain,
        bool $isPortable,
        bool $isOneTimeUse,
        int $inStock,
    ): void {
        $product->setTitle($title);
        $product->setDescription($description);
        $product->setPrice($price);
        $product->setPictureUrl($pictureUrl);
        $product->setIsPublished($isPublished);
        $product->setPowerSupplyType($powerSupplyType);
        $product->setMedicalDomain($medicalDomain);
        $product->setIsPortable($isPortable);
        $product->setIsOneTimeUse($isOneTimeUse);
        $product->setInStock($inStock);

        if ($categoryId !== null) {
            $category = $this->categoryRepository->find($categoryId);
            if (!$category) {
                throw new \InvalidArgumentException('Catégorie introuvable.');
            }

            $product->setCategory($category);
        }
    }

    private function mapListItem(Product $product): AdminProductListItemDto
    {
        $category = $product->getCategory();
        $activeDiscount = $this->resolveActiveDiscount($product);

        return new AdminProductListItemDto(
            (int) $product->getId(),
            (string) $product->getTitle(),
            (string) $product->getDescription(),
            (string) $product->getPrice(),
            (string) $product->getPictureUrl(),
            (int) $product->getInStock(),
            (bool) $product->isPublished(),
            (string) $product->getPowerSupplyType(),
            (string) $product->getMedicalDomain(),
            (bool) $product->isPortable(),
            (bool) $product->isOneTimeUse(),
            (int) $category->getId(),
            (string) $category->getTitle(),
            $activeDiscount?->getPercentage(),
            $activeDiscount?->getEndDate()?->format('Y-m-d'),
        );
    }

    private function mapDetail(Product $product): AdminProductDetailDto
    {
        $discounts = [];
        foreach ($product->getDiscounts() as $discount) {
            $discounts[] = [
                'id' => $discount->getId(),
                'percentage' => $discount->getPercentage(),
                'startDate' => $discount->getStartDate()?->format('Y-m-d'),
                'endDate' => $discount->getEndDate()?->format('Y-m-d'),
            ];
        }

        $translations = [];
        foreach ($product->getTranslations() as $translation) {
            $translations[$translation->getLocale()] = [
                'title' => $translation->getTitle(),
                'description' => $translation->getDescription(),
                'powerSupplyType' => $translation->getPowerSupplyType(),
                'medicalDomain' => $translation->getMedicalDomain(),
            ];
        }

        $category = $product->getCategory();

        return new AdminProductDetailDto(
            (int) $product->getId(),
            (string) $product->getTitle(),
            (string) $product->getDescription(),
            (string) $product->getPrice(),
            (string) $product->getPictureUrl(),
            (int) $product->getInStock(),
            (bool) $product->isPublished(),
            (string) $product->getPowerSupplyType(),
            (string) $product->getMedicalDomain(),
            (bool) $product->isPortable(),
            (bool) $product->isOneTimeUse(),
            [
                'id' => $category->getId(),
                'title' => $category->getTitle(),
                'pictureUrl' => $category->getPictureUrl(),
            ],
            $discounts,
            $translations,
        );
    }

    private function resolveActiveDiscount(Product $product): ?Discount
    {
        $today = new \DateTimeImmutable('today');

        foreach ($product->getDiscounts() as $discount) {
            $startDate = $discount->getStartDate();
            $endDate = $discount->getEndDate();

            if ($startDate === null || $endDate === null) {
                continue;
            }

            if ($startDate <= $today && $endDate >= $today) {
                return $discount;
            }
        }

        return null;
    }

    private function syncProductTranslations(Product $product, array $translations): void
    {
        foreach ($translations as $locale => $translationData) {
            if (empty($translationData['title'])) {
                continue;
            }

            $existing = null;
            foreach ($product->getTranslations() as $translation) {
                if ($translation->getLocale() === $locale) {
                    $existing = $translation;
                    break;
                }
            }

            if (!$existing) {
                $existing = new ProductTranslation();
                $existing->setProduct($product);
                $existing->setLocale($locale);
                $this->entityManager->persist($existing);
            }

            $existing->setTitle($translationData['title']);
            $existing->setDescription($translationData['description'] ?? null);
            $existing->setPowerSupplyType($translationData['powerSupplyType'] ?? null);
            $existing->setMedicalDomain($translationData['medicalDomain'] ?? null);
        }
    }
}
