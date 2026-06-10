<?php

namespace App\Controller\Admin;

use App\Dto\Admin\Product\AdminProductListQueryDto;
use App\Dto\Admin\Product\BulkProductActionDto;
use App\Dto\Admin\Product\CreateProductDto;
use App\Dto\Admin\Product\UpdateProductDto;
use App\Entity\Product;
use App\Exception\InvalidBulkActionException;
use App\Exception\ProductDeletionNotAllowedException;
use App\Service\Admin\Product\AdminProductService;
use App\Service\Admin\Product\ProductBulkActionService;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/products')]
#[IsGranted('ROLE_ADMIN')]
final class AdminProductController extends AbstractController
{
    public function __construct(
        private readonly AdminProductService $adminProductService,
        private readonly ProductBulkActionService $productBulkActionService,
    ) {
    }

    #[Route('', name: 'api_admin_products_index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $query = new AdminProductListQueryDto(
            max(1, $request->query->getInt('page', 1)),
            max(1, min(100, $request->query->getInt('limit', 20))),
            (string) $request->query->get('sortBy', 'id'),
            (string) $request->query->get('sortDirection', 'DESC'),
            $request->query->get('search'),
            $request->query->has('categoryId') ? $request->query->getInt('categoryId') : null,
            $request->query->has('isPublished') ? filter_var($request->query->get('isPublished'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) : null,
        );

        return $this->json($this->adminProductService->list($query));
    }

    #[Route('/{id}', name: 'api_admin_products_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Product $product): JsonResponse
    {
        return $this->json($this->adminProductService->detail($product));
    }

    #[Route('', name: 'api_admin_products_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);

        try {
            $dto = new CreateProductDto(
                $this->requireString($data, 'title'),
                $this->optionalString($data, 'description'),
                $this->requireString($data, 'price'),
                $this->optionalString($data, 'pictureUrl'),
                $this->requireInt($data, 'categoryId'),
                $this->boolOrDefault($data, 'isPublished', false),
                $this->optionalString($data, 'powerSupplyType'),
                $this->optionalString($data, 'medicalDomain'),
                $this->boolOrDefault($data, 'isPortable', false),
                $this->boolOrDefault($data, 'isOneTimeUse', false),
                $this->requireInt($data, 'inStock'),
                is_array($data['translations'] ?? null) ? $data['translations'] : [],
            );

            return $this->json($this->adminProductService->create($dto), Response::HTTP_CREATED);
        } catch (InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}', name: 'api_admin_products_update', requirements: ['id' => '\d+'], methods: ['PATCH', 'PUT'])]
    public function update(Product $product, Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);

        try {
            $dto = new UpdateProductDto(
                $data['title'] ?? null,
                $data['description'] ?? null,
                $data['price'] ?? null,
                $data['pictureUrl'] ?? null,
                $this->nullableInt($data, 'categoryId'),
                $this->nullableBool($data, 'isPublished'),
                $data['powerSupplyType'] ?? null,
                $data['medicalDomain'] ?? null,
                $this->nullableBool($data, 'isPortable'),
                $this->nullableBool($data, 'isOneTimeUse'),
                $this->nullableInt($data, 'inStock'),
                is_array($data['translations'] ?? null) ? $data['translations'] : null,
            );

            return $this->json($this->adminProductService->update($product, $dto));
        } catch (InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}', name: 'api_admin_products_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(Product $product): JsonResponse
    {
        try {
            $this->adminProductService->delete($product);

            return $this->json(['message' => 'Produit supprimé']);
        } catch (ProductDeletionNotAllowedException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_CONFLICT);
        }
    }

    #[Route('/bulk', name: 'api_admin_products_bulk', methods: ['POST'])]
    public function bulk(Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);

        try {
            if (!isset($data['productIds']) || !is_array($data['productIds'])) {
                throw new InvalidArgumentException('Le champ "productIds" est requis.');
            }

            $dto = new BulkProductActionDto(
                array_map('intval', $data['productIds']),
                strtolower((string) ($data['action'] ?? '')),
                array_key_exists('discountPercentage', $data) ? (int) $data['discountPercentage'] : null,
                $data['discountStartDate'] ?? null,
                $data['discountEndDate'] ?? null,
                array_key_exists('isPublished', $data) ? (bool) $data['isPublished'] : null,
            );

            return $this->json($this->productBulkActionService->execute($dto));
        } catch (InvalidBulkActionException|InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (ProductDeletionNotAllowedException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_CONFLICT);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(Request $request): array
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            throw new InvalidArgumentException('JSON invalide.');
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function requireString(array $data, string $key): string
    {
        if (!array_key_exists($key, $data) || $data[$key] === null) {
            throw new InvalidArgumentException(sprintf('Le champ "%s" est requis.', $key));
        }

        if (is_string($data[$key])) {
            if ($data[$key] === '') {
                throw new InvalidArgumentException(sprintf('Le champ "%s" est requis.', $key));
            }

            return $data[$key];
        }

        if (is_scalar($data[$key])) {
            return (string) $data[$key];
        }

        throw new InvalidArgumentException(sprintf('Le champ "%s" est requis.', $key));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function optionalString(array $data, string $key): string
    {
        if (!array_key_exists($key, $data) || $data[$key] === null) {
            return '';
        }

        if (is_scalar($data[$key])) {
            return (string) $data[$key];
        }

        return '';
    }

    /**
     * @param array<string, mixed> $data
     */
    private function requireInt(array $data, string $key): int
    {
        if (!isset($data[$key]) || !is_numeric($data[$key])) {
            throw new InvalidArgumentException(sprintf('Le champ "%s" est requis.', $key));
        }

        return (int) $data[$key];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function nullableInt(array $data, string $key): ?int
    {
        if (!array_key_exists($key, $data) || $data[$key] === null || $data[$key] === '') {
            return null;
        }

        if (!is_numeric($data[$key])) {
            throw new InvalidArgumentException(sprintf('Le champ "%s" doit être numérique.', $key));
        }

        return (int) $data[$key];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function nullableBool(array $data, string $key): ?bool
    {
        if (!array_key_exists($key, $data) || $data[$key] === null || $data[$key] === '') {
            return null;
        }

        $value = filter_var($data[$key], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        if ($value === null) {
            throw new InvalidArgumentException(sprintf('Le champ "%s" doit être booléen.', $key));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function boolOrDefault(array $data, string $key, bool $default): bool
    {
        if (!array_key_exists($key, $data) || $data[$key] === null || $data[$key] === '') {
            return $default;
        }

        $value = filter_var($data[$key], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        if ($value === null) {
            throw new InvalidArgumentException(sprintf('Le champ "%s" doit être booléen.', $key));
        }

        return $value;
    }
}
