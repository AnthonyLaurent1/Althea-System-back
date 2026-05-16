<?php

namespace App\Command;

use App\Entity\Category;
use App\Entity\CategoryTranslation;
use App\Entity\ProductTranslation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-translations-en',
    description: 'Genere les traductions anglaises pour les categories et produits'
)]
class SeedTranslationsEnCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Generation des traductions anglaises');

        $categoryTranslations = [
            'Diagnostic' => 'Diagnostics',
            'Chirurgie' => 'Surgery',
            'Mobilier' => 'Furniture',
        ];

        $categories = $this->entityManager
            ->getRepository(Category::class)
            ->findAll();

        if (!$categories) {
            $io->warning('Aucune categorie trouvee.');
            return Command::SUCCESS;
        }

        foreach ($categories as $category) {
            $frTitle = $category->getTitle();
            $enTitle = $categoryTranslations[$frTitle] ?? $frTitle;

            $existingCategoryTranslation = $this->entityManager
                ->getRepository(CategoryTranslation::class)
                ->findOneBy([
                    'category' => $category,
                    'locale' => 'en',
                ]);

            if (!$existingCategoryTranslation) {
                $categoryTranslation = new CategoryTranslation();
                $categoryTranslation->setCategory($category);
                $categoryTranslation->setLocale('en');
                $categoryTranslation->setTitle($enTitle);

                $this->entityManager->persist($categoryTranslation);

                $io->section("Categorie traduite : {$frTitle} -> {$enTitle}");
            } else {
                $io->text("Categorie deja traduite : {$frTitle}");
            }

            foreach ($category->getProducts() as $product) {
                $existingProductTranslation = $this->entityManager
                    ->getRepository(ProductTranslation::class)
                    ->findOneBy([
                        'product' => $product,
                        'locale' => 'en',
                    ]);

                if ($existingProductTranslation) {
                    $io->text("Produit deja traduit : {$product->getTitle()}");
                    continue;
                }

                $productTranslation = new ProductTranslation();
                $productTranslation->setProduct($product);
                $productTranslation->setLocale('en');
                $productTranslation->setTitle($this->translateProductTitle($product->getTitle(), $frTitle, $enTitle));
                $productTranslation->setDescription(
                    "This is a detailed description for {$product->getTitle()} in the {$enTitle} category."
                );
                $productTranslation->setPowerSupplyType(
                    $product->getPowerSupplyType() === 'Batterie/Secteur'
                        ? 'Battery / Mains'
                        : $product->getPowerSupplyType()
                );
                $productTranslation->setMedicalDomain($enTitle);

                $this->entityManager->persist($productTranslation);
            }

            $io->text("-> Traductions produits generees pour {$frTitle}");
        }

        $this->entityManager->flush();

        $io->success('Traductions anglaises generees avec succes.');

        return Command::SUCCESS;
    }

    private function translateProductTitle(string $title, string $frCategory, string $enCategory): string
    {
        $translatedTitle = str_replace('Produit', 'Product', $title);
        $translatedTitle = str_replace($frCategory, $enCategory, $translatedTitle);

        return $translatedTitle;
    }
}
