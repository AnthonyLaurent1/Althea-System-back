<?php

namespace App\Controller;

use App\Entity\Product;
use App\Dto\ProductDto;
use App\Dto\DiscountDto;
use App\Dto\CategorySimpleDto;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/products')]
class ProductController extends AbstractController
{
    #[Route('', name: 'api_product_index', methods: ['GET'])]
    public function index(ProductRepository $repository): JsonResponse
    {
        $products = $repository->findAll();
        $data = array_map(fn(Product $p) => $this->transformToDto($p), $products);

        return $this->json($data);
    }

    #[Route('/{id}', name: 'api_product_show', methods: ['GET'])]
    public function show(Product $product): JsonResponse
    {
        return $this->json($this->transformToDto($product));
    }

    #[Route('', name: 'api_product_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, CategoryRepository $catRepo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $product = new Product();
        $this->hydrateProduct($product, $data, $catRepo);

        $em->persist($product);
        $em->flush();

        return $this->json($this->transformToDto($product), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_product_update', methods: ['PUT', 'PATCH'])]
    public function update(Product $product, Request $request, EntityManagerInterface $em, CategoryRepository $catRepo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $this->hydrateProduct($product, $data, $catRepo);

        $em->flush();

        return $this->json($this->transformToDto($product));
    }

    /**
     * TRANSFORMATION : Entité -> DTO
     */
    private function transformToDto(Product $product): ProductDto
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

        $cat = $product->getCategory();
        $categorySimpleDto = new CategorySimpleDto(
            $cat->getId(),
            $cat->getTitle(),
            $cat->getPictureUrl()
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

    /**
     * HYDRATATION : JSON -> Entité
     */
    private function hydrateProduct(Product $product, array $data, CategoryRepository $catRepo): void
    {
        $product->setTitle($data['title'] ?? $product->getTitle());
        $product->setDescription($data['description'] ?? $product->getDescription());
        $product->setPrice($data['price'] ?? $product->getPrice());
        $product->setPictureUrl($data['pictureUrl'] ?? $product->getPictureUrl());
        $product->setInStock($data['inStock'] ?? $product->getInStock());
        $product->setIsPublished($data['isPublished'] ?? $product->isPublished());
        $product->setIsPortable($data['isPortable'] ?? $product->isPortable());
        $product->setIsOneTimeUse($data['isOneTimeUse'] ?? $product->isOneTimeUse());
        $product->setPowerSupplyType($data['powerSupplyType'] ?? $product->getPowerSupplyType());
        $product->setMedicalDomain($data['medicalDomain'] ?? $product->getMedicalDomain());

        if (isset($data['categoryId'])) {
            $category = $catRepo->find($data['categoryId']);
            if ($category) {
                $product->setCategory($category);
            }
        }
    }
}
