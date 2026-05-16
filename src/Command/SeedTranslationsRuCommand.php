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
    name: 'app:seed-translations-ru',
    description: 'Genere les traductions russes pour les categories et produits'
)]
class SeedTranslationsRuCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Generation des traductions russes');

        $categoryTranslations = [
            'Diagnostic' => 'Диагностика',
            'Chirurgie' => 'Хирургия',
            'Mobilier' => 'Мебель',
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
            $ruTitle = $categoryTranslations[$frTitle] ?? $frTitle;

            $existingCategoryTranslation = $this->entityManager
                ->getRepository(CategoryTranslation::class)
                ->findOneBy([
                    'category' => $category,
                    'locale' => 'ru',
                ]);

            if (!$existingCategoryTranslation) {
                $categoryTranslation = new CategoryTranslation();
                $categoryTranslation->setCategory($category);
                $categoryTranslation->setLocale('ru');
                $categoryTranslation->setTitle($ruTitle);

                $this->entityManager->persist($categoryTranslation);

                $io->section("Categorie traduite : {$frTitle} -> {$ruTitle}");
            } else {
                $io->text("Categorie deja traduite : {$frTitle}");
            }

            foreach ($category->getProducts() as $product) {
                $existingProductTranslation = $this->entityManager
                    ->getRepository(ProductTranslation::class)
                    ->findOneBy([
                        'product' => $product,
                        'locale' => 'ru',
                    ]);

                if ($existingProductTranslation) {
                    $io->text("Produit deja traduit : {$product->getTitle()}");
                    continue;
                }

                $productTranslation = new ProductTranslation();
                $productTranslation->setProduct($product);
                $productTranslation->setLocale('ru');
                $productTranslation->setTitle($this->translateProductTitle($product->getTitle(), $frTitle, $ruTitle));
                $productTranslation->setDescription(
                    "Это подробное описание товара {$product->getTitle()} из категории {$ruTitle}."
                );
                $productTranslation->setPowerSupplyType(
                    $product->getPowerSupplyType() === 'Batterie/Secteur'
                        ? 'Аккумулятор / Сеть'
                        : $product->getPowerSupplyType()
                );
                $productTranslation->setMedicalDomain($ruTitle);

                $this->entityManager->persist($productTranslation);
            }

            $io->text("-> Traductions produits generees pour {$frTitle}");
        }

        $this->entityManager->flush();

        $io->success('Traductions russes generees avec succes.');

        return Command::SUCCESS;
    }

    private function translateProductTitle(string $title, string $frCategory, string $ruCategory): string
    {
        $translatedTitle = str_replace('Produit', 'Товар', $title);
        $translatedTitle = str_replace($frCategory, $ruCategory, $translatedTitle);

        return $translatedTitle;
    }
}
