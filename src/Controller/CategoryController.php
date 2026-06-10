<?php

namespace App\Controller;

use App\Dto\CategoryDto;
use App\Dto\CategorySimpleDto;
use App\Dto\DiscountDto;
use App\Entity\Category;
use App\Dto\ProductDto;
use App\Entity\CategoryTranslation;
use App\Entity\Product;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/categories')]
class CategoryController extends AbstractController
{
    #[Route('', name: 'api_category_index', methods: ['GET'])]
    public function index(CategoryRepository $repository, Request $request): JsonResponse
    {
        $locale = $request->query->get('locale', 'fr');

        $categories = $repository->findAll();
        $data = array_map(fn(Category $c) => $this->transformCategoryToDto($c, $locale), $categories);

        return $this->json($data);
    }

    #[Route('/{id}', name: 'api_category_show', methods: ['GET'])]
    public function show(Category $category, Request $request): JsonResponse
    {
        $locale = $request->query->get('locale', 'fr');

        return $this->json($this->transformCategoryToDto($category, $locale));
    }

    #[Route('', name: 'api_category_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $category = new Category();

        $this->hydrateCategory($category, $data);
        $em->persist($category);

        $this->syncCategoryTranslations($category, $data['translations'] ?? [], $em);

        $em->flush();

        return $this->json($this->transformCategoryToDto($category), Response::HTTP_CREATED);
    }

    #[Route('/reorder', name: 'api_category_reorder', methods: ['PATCH'])]
    #[IsGranted('ROLE_ADMIN')]
    public function reorder(Request $request, EntityManagerInterface $em, CategoryRepository $repository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data) || !isset($data['orderedIds']) || !is_array($data['orderedIds'])) {
            return $this->json(['error' => 'Le champ "orderedIds" est requis.'], Response::HTTP_BAD_REQUEST);
        }

        $position = 0;
        foreach ($data['orderedIds'] as $id) {
            $category = $repository->find((int) $id);
            if ($category !== null) {
                $category->setDisplayOrder($position);
                ++$position;
            }
        }

        $em->flush();

        $categories = $repository->findBy([], ['displayOrder' => 'ASC', 'id' => 'ASC']);

        return $this->json(array_map(fn(Category $c) => $this->transformCategoryToDto($c), $categories));
    }

    #[Route('/{id}', name: 'api_category_update', requirements: ['id' => '\d+'], methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(Category $category, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $this->hydrateCategory($category, $data);

        $this->syncCategoryTranslations($category, $data['translations'] ?? [], $em);

        $em->flush();

        return $this->json($this->transformCategoryToDto($category));
    }

    #[Route('/{id}', name: 'api_category_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Category $category, EntityManagerInterface $em): JsonResponse
    {
        if (!$category->getProducts()->isEmpty()) {
            return $this->json(
                ['error' => 'Impossible de supprimer une catégorie contenant des produits.'],
                Response::HTTP_CONFLICT
            );
        }

        $em->remove($category);
        $em->flush();

        return $this->json(['message' => 'Catégorie supprimée']);
    }

    #[Route('/{id}/products', name: 'api_category_products', methods: ['GET'])]
    public function getProductsByCategory(Category $category, Request $request): JsonResponse
    {
        $locale = $request->query->get('locale', 'fr');

        $products = $category->getProducts();

        $data = array_map(fn(Product $p) => $this->transformProductToDto($p, $locale), $products->toArray());

        return $this->json($data);
    }


    /**
     * Transforme une Catégorie en CategoryDto
     */
    private function transformCategoryToDto(Category $category, string $locale = 'fr'): CategoryDto
    {
        $title = $category->getTitle();

        if ($locale !== 'fr') {
            foreach ($category->getTranslations() as $translation) {
                if ($translation->getLocale() === $locale) {
                    $title = $translation->getTitle();
                    break;
                }
            }
        }

        $productDtos = [];
        foreach ($category->getProducts() as $product) {
            $productDtos[] = $this->transformProductToDto($product, $locale);
        }

        return new CategoryDto(
            $category->getId(),
            $title,
            $category->getPictureUrl(),
            $productDtos,
            $category->getDisplayOrder()
        );
    }

    /**
     * Transforme un Produit en ProductDto
     */
    private function transformProductToDto(Product $product, string $locale = 'fr'): ProductDto
    {
        $title = $product->getTitle();
        $description = $product->getDescription() ?? '';
        $powerSupplyType = $product->getPowerSupplyType() ?? 'N/A';
        $medicalDomain = $product->getMedicalDomain() ?? 'N/A';

        if ($locale !== 'fr') {
            foreach ($product->getTranslations() as $translation) {
                if ($translation->getLocale() === $locale) {
                    $title = $translation->getTitle() ?? $title;
                    $description = $translation->getDescription() ?? $description;
                    $powerSupplyType = $translation->getPowerSupplyType() ?? $powerSupplyType;
                    $medicalDomain = $translation->getMedicalDomain() ?? $medicalDomain;
                    break;
                }
            }
        }

        $categoryEntity = $product->getCategory();
        $categoryTitle = $categoryEntity->getTitle();

        if ($locale !== 'fr') {
            foreach ($categoryEntity->getTranslations() as $translation) {
                if ($translation->getLocale() === $locale) {
                    $categoryTitle = $translation->getTitle();
                    break;
                }
            }
        }

        $categorySimpleDto = new CategorySimpleDto(
            $categoryEntity->getId(),
            $categoryTitle,
            $categoryEntity->getPictureUrl()
        );

        $discountDtos = [];
        foreach ($product->getDiscounts() as $discount) {
            $discountDtos[] = new DiscountDto(
                $discount->getId(),
                $discount->getPercentage(),
                $discount->getStartDate()->format('Y-m-d'),
                $discount->getEndDate()->format('Y-m-d')
            );
        }

        return new ProductDto(
            $product->getId(),
            $title,
            $description,
            $product->getPrice(),
            $product->getPictureUrl() ?? '',
            $product->getInStock() ?? 0,
            $product->isPublished() ?? false,
            $product->isPortable() ?? false,
            $product->isOneTimeUse() ?? false,
            $powerSupplyType,
            $medicalDomain,
            $categorySimpleDto,
            $discountDtos
        );
    }

    private function syncCategoryTranslations(Category $category, array $translations, EntityManagerInterface $em): void
    {
        foreach ($translations as $locale => $translationData) {
            if (empty($translationData['title'])) {
                continue;
            }

            $existing = null;
            foreach ($category->getTranslations() as $translation) {
                if ($translation->getLocale() === $locale) {
                    $existing = $translation;
                    break;
                }
            }

            if (!$existing) {
                $existing = new CategoryTranslation();
                $existing->setCategory($category);
                $existing->setLocale($locale);
                $em->persist($existing);
            }

            $existing->setTitle($translationData['title']);
        }
    }


    private function hydrateCategory(Category $category, array $data): void
    {
        $category->setTitle($data['title'] ?? $category->getTitle());
        $category->setPictureUrl($data['pictureUrl'] ?? $category->getPictureUrl());

        if (array_key_exists('displayOrder', $data)) {
            $category->setDisplayOrder((int) $data['displayOrder']);
        }
    }
}
