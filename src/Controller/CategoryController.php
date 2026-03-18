<?php

namespace App\Controller;

use App\Dto\CategoryDto;
use App\Dto\CategorySimpleDto;
use App\Dto\DiscountDto;
use App\Entity\Category;
use App\Dto\ProductDto;
use App\Entity\Product;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/categories')]
class CategoryController extends AbstractController
{
    #[Route('', name: 'api_category_index', methods: ['GET'])]
    public function index(CategoryRepository $repository): JsonResponse
    {
        $categories = $repository->findAll();
        $data = array_map(fn(Category $c) => $this->transformCategoryToDto($c), $categories);

        return $this->json($data);
    }

    #[Route('/{id}', name: 'api_category_show', methods: ['GET'])]
    public function show(Category $category): JsonResponse
    {
        return $this->json($this->transformCategoryToDto($category));
    }

    #[Route('', name: 'api_category_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $category = new Category();
        $this->hydrateCategory($category, $data);

        $em->persist($category);
        $em->flush();

        return $this->json($this->transformCategoryToDto($category), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_category_update', methods: ['PUT', 'PATCH'])]
    public function update(Category $category, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $this->hydrateCategory($category, $data);

        $em->flush();

        return $this->json($this->transformCategoryToDto($category));
    }

    #[Route('/{id}/products', name: 'api_category_products', methods: ['GET'])]
    public function getProductsByCategory(Category $category): JsonResponse
    {
        $products = $category->getProducts();

        $data = array_map(fn(Product $p) => $this->transformProductToDto($p), $products->toArray());

        return $this->json($data);
    }


    /**
     * Transforme une Catégorie en CategoryDto
     */
    private function transformCategoryToDto(Category $category): CategoryDto
    {
        $productDtos = [];
        foreach ($category->getProducts() as $product) {
            $productDtos[] = $this->transformProductToDto($product);
        }

        return new CategoryDto(
            $category->getId(),
            $category->getTitle(),
            $category->getPictureUrl(),
            $productDtos
        );
    }

    /**
     * Transforme un Produit en ProductDto
     */
    private function transformProductToDto(Product $product): ProductDto
    {
        $discountDtos = [];
        foreach ($product->getDiscounts() as $discount) {
            $discountDtos[] = new DiscountDto(
                $discount->getId(),
                $discount->getPercentage(),
                $discount->getStartDate()->format('Y-m-d'),
                $discount->getEndDate()->format('Y-m-d')
            );
        }

        $categoryEntity = $product->getCategory();
        $categorySimpleDto = new CategorySimpleDto(
            $categoryEntity->getId(),
            $categoryEntity->getTitle(),
            $categoryEntity->getPictureUrl()
        );

        return new ProductDto(
            $product->getId(),
            $product->getTitle(),
            $product->getDescription() ?? '',
            $product->getPrice(),
            $product->getPictureUrl() ?? '',
            $product->getInStock() ?? 0,
            $product->isPublished() ?? false,
            $product->isPortable() ?? false,
            $product->isOneTimeUse() ?? false,
            $product->getPowerSupplyType() ?? 'N/A',
            $product->getMedicalDomain() ?? 'N/A',
            $categorySimpleDto,
            $discountDtos
        );
    }


    private function hydrateCategory(Category $category, array $data): void
    {
        $category->setTitle($data['title'] ?? $category->getTitle());
        $category->setPictureUrl($data['pictureUrl'] ?? $category->getPictureUrl());
    }
}
