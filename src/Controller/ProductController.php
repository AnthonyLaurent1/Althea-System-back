<?php

namespace App\Controller;

use App\Entity\Product;
use App\Dto\ProductDto;
use App\Dto\DiscountDto;
use App\Dto\CategorySimpleDto;
use App\Entity\ProductTranslation;
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
    public function index(ProductRepository $repository, Request $request): JsonResponse
    {
        $locale = $request->query->get('locale', 'fr');

        $products = $repository->findAll();
        $data = array_map(fn(Product $p) => $this->transformToDto($p, $locale), $products);

        return $this->json($data);
    }

    #[Route('/{id}', name: 'api_product_show', methods: ['GET'])]
    public function show(Product $product, Request $request): JsonResponse
    {
        $locale = $request->query->get('locale', 'fr');

        return $this->json($this->transformToDto($product, $locale));
    }

    #[Route('', name: 'api_product_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, CategoryRepository $catRepo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $product = new Product();
        $this->hydrateProduct($product, $data, $catRepo);

        $em->persist($product);

        $this->syncProductTranslations($product, $data['translations'] ?? [], $em);

        $em->flush();

        return $this->json($this->transformToDto($product), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_product_update', methods: ['PUT', 'PATCH'])]
    public function update(Product $product, Request $request, EntityManagerInterface $em, CategoryRepository $catRepo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $this->hydrateProduct($product, $data, $catRepo);

        $this->syncProductTranslations($product, $data['translations'] ?? [], $em);

        $em->flush();

        return $this->json($this->transformToDto($product));
    }

    #[Route('/{id}/similar', name: 'api_product_similar', methods: ['GET'])]
    public function getSimilarProducts(Product $product, ProductRepository $repository, Request $request): JsonResponse
    {
        $locale = $request->query->get('locale', 'fr');

        $domain = $product->getMedicalDomain();
        $category = $product->getCategory();

        if ($domain && $domain !== 'N/A' && $domain !== '') {
            $criteria = ['medicalDomain' => $domain];
        } else {
            $criteria = ['category' => $category];
        }

        $similarProducts = $repository->findBy(
            $criteria,
            ['id' => 'DESC'],
            10
        );

        $filtered = array_filter($similarProducts, function(Product $p) use ($product) {
            return $p->getId() !== $product->getId();
        });

        $finalList = array_slice($filtered, 0, 6);

        $data = array_map(fn(Product $p) => $this->transformToDto($p, $locale), $finalList);

        return $this->json($data);
    }

    #[Route('/search', name: 'api_product_search', methods: ['GET'])]
    public function search(Request $request, ProductRepository $repository): JsonResponse
    {
        $locale = $request->query->get('locale', 'fr');
        $searchTerm = $request->query->get('q', '');

        if (strlen($searchTerm) < 2) {
            return $this->json([]);
        }

        $products = $repository->searchByTitle($searchTerm);

        $data = array_map(fn(Product $p) => $this->transformToDto($p, $locale), $products);

        return $this->json($data);
    }

    /**
     * TRANSFORMATION : Entité -> DTO
     */
    private function transformToDto(Product $product, string $locale = 'fr'): ProductDto
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


    private function syncProductTranslations(Product $product, array $translations, EntityManagerInterface $em): void
    {
        foreach ($translations as $locale => $translationData) {
            $existingTranslation = null;

            foreach ($product->getTranslations() as $translation) {
                if ($translation->getLocale() === $locale) {
                    $existingTranslation = $translation;
                    break;
                }
            }

            if (!$existingTranslation) {
                $existingTranslation = new ProductTranslation();
                $existingTranslation->setProduct($product);
                $existingTranslation->setLocale($locale);
                $em->persist($existingTranslation);
            }

            if (array_key_exists('title', $translationData)) {
                $existingTranslation->setTitle($translationData['title']);
            }

            if (array_key_exists('description', $translationData)) {
                $existingTranslation->setDescription($translationData['description']);
            }

            if (array_key_exists('powerSupplyType', $translationData)) {
                $existingTranslation->setPowerSupplyType($translationData['powerSupplyType']);
            }

            if (array_key_exists('medicalDomain', $translationData)) {
                $existingTranslation->setMedicalDomain($translationData['medicalDomain']);
            }
        }
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
