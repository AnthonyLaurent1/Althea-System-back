<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260516162420 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, picture_url VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE category_translation (id INT AUTO_INCREMENT NOT NULL, locale VARCHAR(5) NOT NULL, title VARCHAR(255) NOT NULL, category_id INT DEFAULT NULL, INDEX IDX_3F2070412469DE2 (category_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE contact_request (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, subject VARCHAR(255) NOT NULL, message VARCHAR(255) NOT NULL, status VARCHAR(50) NOT NULL, source VARCHAR(50) NOT NULL, admin_reply LONGTEXT DEFAULT NULL, replied_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE discount (id INT AUTO_INCREMENT NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, percentage INT NOT NULL, product_id INT NOT NULL, INDEX IDX_E1E0B40E4584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE items (id INT AUTO_INCREMENT NOT NULL, quantity INT NOT NULL, price DOUBLE PRECISION NOT NULL, product_id INT NOT NULL, orders_id INT NOT NULL, INDEX IDX_E11EE94D4584665A (product_id), INDEX IDX_E11EE94DCFFE9AD6 (orders_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE orders (id INT AUTO_INCREMENT NOT NULL, payment_date DATE NOT NULL, total_price DOUBLE PRECISION NOT NULL, status VARCHAR(255) NOT NULL, user_id INT NOT NULL, INDEX IDX_E52FFDEEA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, price VARCHAR(255) NOT NULL, picture_url VARCHAR(255) NOT NULL, is_published TINYINT NOT NULL, power_supply_type VARCHAR(255) NOT NULL, medical_domain VARCHAR(255) NOT NULL, is_portable TINYINT NOT NULL, is_one_time_use TINYINT NOT NULL, in_stock INT NOT NULL, category_id INT NOT NULL, INDEX IDX_D34A04AD12469DE2 (category_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE product_translation (id INT AUTO_INCREMENT NOT NULL, locale VARCHAR(5) NOT NULL, title VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, power_supply_type VARCHAR(255) DEFAULT NULL, medical_domain VARCHAR(255) DEFAULT NULL, product_id INT DEFAULT NULL, INDEX IDX_1846DB704584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, hashed_password VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, phone VARCHAR(255) NOT NULL, city VARCHAR(255) NOT NULL, country VARCHAR(255) NOT NULL, address VARCHAR(255) NOT NULL, additional_address VARCHAR(255) NOT NULL, postal_code VARCHAR(255) NOT NULL, company VARCHAR(255) NOT NULL, siret VARCHAR(255) NOT NULL, is_verified TINYINT NOT NULL, confirmation_token VARCHAR(255) DEFAULT NULL, reset_password_token VARCHAR(255) DEFAULT NULL, reset_password_token_expires_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE category_translation ADD CONSTRAINT FK_3F2070412469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE discount ADD CONSTRAINT FK_E1E0B40E4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE items ADD CONSTRAINT FK_E11EE94D4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE items ADD CONSTRAINT FK_E11EE94DCFFE9AD6 FOREIGN KEY (orders_id) REFERENCES orders (id)');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE product_translation ADD CONSTRAINT FK_1846DB704584665A FOREIGN KEY (product_id) REFERENCES product (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category_translation DROP FOREIGN KEY FK_3F2070412469DE2');
        $this->addSql('ALTER TABLE discount DROP FOREIGN KEY FK_E1E0B40E4584665A');
        $this->addSql('ALTER TABLE items DROP FOREIGN KEY FK_E11EE94D4584665A');
        $this->addSql('ALTER TABLE items DROP FOREIGN KEY FK_E11EE94DCFFE9AD6');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEEA76ED395');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD12469DE2');
        $this->addSql('ALTER TABLE product_translation DROP FOREIGN KEY FK_1846DB704584665A');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE category_translation');
        $this->addSql('DROP TABLE contact_request');
        $this->addSql('DROP TABLE discount');
        $this->addSql('DROP TABLE items');
        $this->addSql('DROP TABLE orders');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE product_translation');
        $this->addSql('DROP TABLE user');
    }
}
