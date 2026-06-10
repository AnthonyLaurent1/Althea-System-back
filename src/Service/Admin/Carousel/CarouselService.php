<?php

namespace App\Service\Admin\Carousel;

use App\Entity\CarouselItem;
use App\Repository\CarouselItemRepository;
use Doctrine\ORM\EntityManagerInterface;

final class CarouselService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CarouselItemRepository $carouselItemRepository,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAll(): array
    {
        return array_map(
            fn (CarouselItem $item): array => $this->map($item),
            $this->carouselItemRepository->findAllOrdered()
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listActive(): array
    {
        return array_map(
            fn (CarouselItem $item): array => $this->map($item),
            $this->carouselItemRepository->findActiveOrdered()
        );
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $item = new CarouselItem();
        $item->setTitle($this->requireString($data, 'title'));
        $item->setPictureUrl($this->requireString($data, 'pictureUrl'));
        $item->setSubtitle($this->nullableString($data, 'subtitle'));
        $item->setLink($this->nullableString($data, 'link'));
        $item->setDisplayOrder((int) ($data['displayOrder'] ?? 0));
        $item->setIsActive((bool) ($data['isActive'] ?? true));

        $this->entityManager->persist($item);
        $this->entityManager->flush();

        return $this->map($item);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function update(CarouselItem $item, array $data): array
    {
        if (array_key_exists('title', $data)) {
            $item->setTitle($this->requireString($data, 'title'));
        }
        if (array_key_exists('pictureUrl', $data)) {
            $item->setPictureUrl($this->requireString($data, 'pictureUrl'));
        }
        if (array_key_exists('subtitle', $data)) {
            $item->setSubtitle($this->nullableString($data, 'subtitle'));
        }
        if (array_key_exists('link', $data)) {
            $item->setLink($this->nullableString($data, 'link'));
        }
        if (array_key_exists('displayOrder', $data)) {
            $item->setDisplayOrder((int) $data['displayOrder']);
        }
        if (array_key_exists('isActive', $data)) {
            $item->setIsActive((bool) $data['isActive']);
        }

        $this->entityManager->flush();

        return $this->map($item);
    }

    public function delete(CarouselItem $item): void
    {
        $this->entityManager->remove($item);
        $this->entityManager->flush();
    }

    /**
     * Réordonne les éléments à partir d'un tableau d'IDs.
     *
     * @param int[] $orderedIds
     *
     * @return array<int, array<string, mixed>>
     */
    public function reorder(array $orderedIds): array
    {
        $position = 0;
        foreach ($orderedIds as $id) {
            $item = $this->carouselItemRepository->find((int) $id);
            if ($item !== null) {
                $item->setDisplayOrder($position);
                ++$position;
            }
        }

        $this->entityManager->flush();

        return $this->listAll();
    }

    /**
     * @return array<string, mixed>
     */
    private function map(CarouselItem $item): array
    {
        return [
            'id' => $item->getId(),
            'title' => $item->getTitle(),
            'subtitle' => $item->getSubtitle(),
            'pictureUrl' => $item->getPictureUrl(),
            'link' => $item->getLink(),
            'displayOrder' => $item->getDisplayOrder(),
            'isActive' => $item->isActive(),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function requireString(array $data, string $key): string
    {
        if (!isset($data[$key]) || !is_string($data[$key]) || trim($data[$key]) === '') {
            throw new \InvalidArgumentException(sprintf('Le champ "%s" est requis.', $key));
        }

        return $data[$key];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function nullableString(array $data, string $key): ?string
    {
        if (!array_key_exists($key, $data) || $data[$key] === null || $data[$key] === '') {
            return null;
        }

        return (string) $data[$key];
    }
}
