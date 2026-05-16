<?php

namespace App\Command;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:seed-data', description: 'Génère 3 catégories et 15 produits automatiquement')]
class SeedDataCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $entityManager) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $io = new SymfonyStyle($input, $output);

        $categoriesData = [
            'Diagnostic' => 'https://placehold.co/600x400?text=Diagnostic',
            'Chirurgie' => 'https://placehold.co/600x400?text=Chirurgie',
            'Mobilier' => 'https://placehold.co/600x400?text=Mobilier'
        ];

        $io->title('Lancement de la génération des données...');

        foreach ($categoriesData as $title => $url) {
            $category = new Category();
            $category->setTitle($title);
            $category->setPictureUrl($url);
            $this->entityManager->persist($category);

            $io->section("Création de la catégorie : $title");


            for ($i = 1; $i <= 5; $i++) {
                $product = new Product();
                $product->setTitle("Produit $title #$i");
                $product->setDescription("Ceci est une description détaillée pour le produit $i de la catégorie $title.");
                $product->setPrice((string) rand(50, 500) . ".99");
                $product->setPictureUrl("https://placehold.co/400x400?text=Product+$i");
                $product->setCategory($category);
                $product->setIsPublished(true);
                $product->setInStock(rand(5, 100));
                $product->setIsPortable(rand(0, 1) === 1);
                $product->setIsOneTimeUse(false);
                $product->setPowerSupplyType("Batterie/Secteur");
                $product->setMedicalDomain($title);

                $this->entityManager->persist($product);
            }

            $io->text("-> 5 produits ajoutés à $title");
        }

        $this->entityManager->flush();

        $io->success('Base de données alimentée avec succès !');

        return Command::SUCCESS;
    }
}
