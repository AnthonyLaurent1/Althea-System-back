<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260120104324 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, picture_url VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE discount (id INT AUTO_INCREMENT NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, percentage INT NOT NULL, product_id INT NOT NULL, INDEX IDX_E1E0B40E4584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE item (id INT AUTO_INCREMENT NOT NULL, quantity INT NOT NULL, price DOUBLE PRECISION NOT NULL, product_id INT NOT NULL, order_id_id INT NOT NULL, INDEX IDX_1F1B251E4584665A (product_id), INDEX IDX_1F1B251EFCDAEAAA (order_id_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `order` (id INT AUTO_INCREMENT NOT NULL, payment_date DATE NOT NULL, total_price DOUBLE PRECISION NOT NULL, user_id INT NOT NULL, INDEX IDX_F5299398A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, price VARCHAR(255) NOT NULL, picture_url VARCHAR(255) NOT NULL, is_published TINYINT NOT NULL, power_supply_type VARCHAR(255) NOT NULL, medical_domain VARCHAR(255) NOT NULL, is_portable TINYINT NOT NULL, is_one_time_use TINYINT NOT NULL, in_stock INT NOT NULL, category_id INT NOT NULL, INDEX IDX_D34A04AD12469DE2 (category_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, phone VARCHAR(255) NOT NULL, city VARCHAR(255) NOT NULL, country VARCHAR(255) NOT NULL, address VARCHAR(255) NOT NULL, addiotional_address VARCHAR(255) NOT NULL, postal_code VARCHAR(255) NOT NULL, company VARCHAR(255) NOT NULL, siret VARCHAR(255) NOT NULL, is_verified TINYINT NOT NULL, role VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE discount ADD CONSTRAINT FK_E1E0B40E4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE item ADD CONSTRAINT FK_1F1B251E4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE item ADD CONSTRAINT FK_1F1B251EFCDAEAAA FOREIGN KEY (order_id_id) REFERENCES `order` (id)');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE discount DROP FOREIGN KEY FK_E1E0B40E4584665A');
        $this->addSql('ALTER TABLE item DROP FOREIGN KEY FK_1F1B251E4584665A');
        $this->addSql('ALTER TABLE item DROP FOREIGN KEY FK_1F1B251EFCDAEAAA');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398A76ED395');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD12469DE2');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE discount');
        $this->addSql('DROP TABLE item');
        $this->addSql('DROP TABLE `order`');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE user');
    }
}
