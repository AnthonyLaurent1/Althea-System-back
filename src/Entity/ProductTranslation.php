<?php

namespace App\Entity;

use App\Repository\ProductTranslationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductTranslationRepository::class)]
class ProductTranslation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'translations')]
    private ?Product $product = null;

    #[ORM\Column(length: 5)]
    private ?string $locale = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $powerSupplyType = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $medicalDomain = null;

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

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
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

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPowerSupplyType(): ?string
    {
        return $this->powerSupplyType;
    }

    public function setPowerSupplyType(?string $powerSupplyType): static
    {
        $this->powerSupplyType = $powerSupplyType;

        return $this;
    }

    public function getMedicalDomain(): ?string
    {
        return $this->medicalDomain;
    }

    public function setMedicalDomain(?string $medicalDomain): static
    {
        $this->medicalDomain = $medicalDomain;

        return $this;
    }
}
