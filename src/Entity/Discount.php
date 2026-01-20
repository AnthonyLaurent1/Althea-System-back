<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\DiscountRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: DiscountRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['discount:read']],
    denormalizationContext: ['groups' => ['discount:write']]
)]
class Discount
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['discount:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'discounts')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['discount:read', 'discount:write'])]
    private ?Product $product = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(['discount:read', 'discount:write'])]
    private ?\DateTime $startDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(['discount:read', 'discount:write'])]
    private ?\DateTime $endDate = null;

    #[ORM\Column]
    #[Groups(['discount:read', 'discount:write'])]
    private ?int $percentage = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTime $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTime
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTime $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getPercentage(): ?int
    {
        return $this->percentage;
    }

    public function setPercentage(int $percentage): static
    {
        $this->percentage = $percentage;

        return $this;
    }
}
