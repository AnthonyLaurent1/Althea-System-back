<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['product:read']],
    denormalizationContext: ['groups' => ['product:write']]
)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['product:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['product:read', 'product:write'])]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    #[Groups(['product:read', 'product:write'])]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    #[Groups(['product:read', 'product:write'])]
    private ?string $price = null;

    #[ORM\Column(length: 255)]
    #[Groups(['product:read', 'product:write'])]
    private ?string $pictureUrl = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['product:read', 'product:write'])]
    private ?Category $category = null;

    #[ORM\Column]
    #[Groups(['product:read', 'product:write'])]
    private ?bool $isPublished = null;

    #[ORM\Column(length: 255)]
    #[Groups(['product:read', 'product:write'])]
    private ?string $powerSupplyType = null;

    #[ORM\Column(length: 255)]
    #[Groups(['product:read', 'product:write'])]
    private ?string $medicalDomain = null;

    #[ORM\Column]
    #[Groups(['product:read', 'product:write'])]
    private ?bool $isPortable = null;

    #[ORM\Column]
    #[Groups(['product:read', 'product:write'])]
    private ?bool $isOneTimeUse = null;

    #[ORM\Column]
    #[Groups(['product:read', 'product:write'])]
    private ?int $inStock = null;

    /**
     * @var Collection<int, Discount>
     */
    #[ORM\OneToMany(targetEntity: Discount::class, mappedBy: 'product')]
    #[Groups(['product:read'])]
    private Collection $discounts;

    public function __construct()
    {
        $this->discounts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getPictureUrl(): ?string
    {
        return $this->pictureUrl;
    }

    public function setPictureUrl(string $pictureUrl): static
    {
        $this->pictureUrl = $pictureUrl;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function isPublished(): ?bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): static
    {
        $this->isPublished = $isPublished;

        return $this;
    }

    public function getPowerSupplyType(): ?string
    {
        return $this->powerSupplyType;
    }

    public function setPowerSupplyType(string $powerSupplyType): static
    {
        $this->powerSupplyType = $powerSupplyType;

        return $this;
    }

    public function getMedicalDomain(): ?string
    {
        return $this->medicalDomain;
    }

    public function setMedicalDomain(string $medicalDomain): static
    {
        $this->medicalDomain = $medicalDomain;

        return $this;
    }

    public function isPortable(): ?bool
    {
        return $this->isPortable;
    }

    public function setIsPortable(bool $isPortable): static
    {
        $this->isPortable = $isPortable;

        return $this;
    }

    public function isOneTimeUse(): ?bool
    {
        return $this->isOneTimeUse;
    }

    public function setIsOneTimeUse(bool $isOneTimeUse): static
    {
        $this->isOneTimeUse = $isOneTimeUse;

        return $this;
    }

    public function getInStock(): ?int
    {
        return $this->inStock;
    }

    public function setInStock(int $inStock): static
    {
        $this->inStock = $inStock;

        return $this;
    }

    /**
     * @return Collection<int, Discount>
     */
    public function getDiscounts(): Collection
    {
        return $this->discounts;
    }

    public function addDiscount(Discount $discount): static
    {
        if (!$this->discounts->contains($discount)) {
            $this->discounts->add($discount);
            $discount->setProduct($this);
        }

        return $this;
    }

    public function removeDiscount(Discount $discount): static
    {
        if ($this->discounts->removeElement($discount)) {
            // set the owning side to null (unless already changed)
            if ($discount->getProduct() === $this) {
                $discount->setProduct(null);
            }
        }

        return $this;
    }
}
