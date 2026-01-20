<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 3; $i++) {
            $category = new Category();
            $category->setTitle('Catégorie ' . $i);
            $category->setPictureUrl('Category_' . $i . '.jpg');
            $manager->persist($category);
            $this->addReference('category' . $i, $category);
        }

        $manager->flush();
    }
}
