<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $price = null;

    #[ORM\Column(length: 255)]
    private ?string $pictureUrl = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    #[ORM\Column]
    private ?bool $isPublished = null;

    #[ORM\Column(length: 255)]
    private ?string $powerSupplyType = null;

    #[ORM\Column(length: 255)]
    private ?string $medicalDomain = null;

    #[ORM\Column]
    private ?bool $isPortable = null;

    #[ORM\Column]
    private ?bool $isOneTimeUse = null;

    #[ORM\Column]
    private ?int $inStock = null;

    /**
     * @var Collection<int, Discount>
     */
    #[ORM\OneToMany(targetEntity: Discount::class, mappedBy: 'product')]
    private Collection $discounts;

    /**
     * @var Collection<int, ProductTranslation>
     */
    #[ORM\OneToMany(targetEntity: ProductTranslation::class, mappedBy: 'product', cascade: ['persist', 'remove'])]
    private Collection $translations;

    public function __construct()
    {
        $this->discounts = new ArrayCollection();
        $this->translations = new ArrayCollection();
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
            if ($discount->getProduct() === $this) {
                $discount->setProduct(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, ProductTranslation>
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(ProductTranslation $translation): static
    {
        if (!$this->translations->contains($translation)) {
            $this->translations->add($translation);
            $translation->setProduct($this);
        }

        return $this;
    }

    public function removeTranslation(ProductTranslation $translation): static
    {
        if ($this->translations->removeElement($translation)) {
            // set the owning side to null (unless already changed)
            if ($translation->getProduct() === $this) {
                $translation->setProduct(null);
            }
        }

        return $this;
    }
}
