<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 8; $i++) {
            $product = new Product();
            $product->setTitle('Produit ' . $i);
            $product->setDescription('Description du produit ' . $i);
            $product->setIsPublished(true);
            $product->setPictureUrl('Image_' . $i . '.jpg');
            $product->setPrice(mt_rand(100, 1000));
            $product->setPowerSupplyType('Type ' . $i);
            $product->setMedicalDomain('Domaine ' . $i);
            $product->setIsPortable(true);
            $product->setIsOneTimeUse(true);
            $product->setInStock(mt_rand(0, 50));
            $product->setCategory($this->getReference('category' . mt_rand(0, 2), Category::class));
            $manager->persist($product);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CategoryFixtures::class,
        ];
    }
}
