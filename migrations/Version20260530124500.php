<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Backoffice : carrousel, top produits homepage, logs chatbot, ordre des catégories.
 */
final class Version20260530124500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Tables carousel_item, chatbot_log, top_product + colonne display_order sur category';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE carousel_item (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, subtitle VARCHAR(255) DEFAULT NULL, picture_url VARCHAR(255) NOT NULL, link VARCHAR(255) DEFAULT NULL, display_order INT NOT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE chatbot_log (id INT AUTO_INCREMENT NOT NULL, session_id VARCHAR(255) NOT NULL, user_message LONGTEXT NOT NULL, bot_response LONGTEXT DEFAULT NULL, matched_intent VARCHAR(100) DEFAULT NULL, category VARCHAR(100) DEFAULT NULL, locale VARCHAR(5) NOT NULL, escalated TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_CHATBOT_LOG_SESSION (session_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE top_product (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, position INT NOT NULL, INDEX IDX_TOP_PRODUCT_PRODUCT (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE top_product ADD CONSTRAINT FK_TOP_PRODUCT_PRODUCT FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE category ADD display_order INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE top_product DROP FOREIGN KEY FK_TOP_PRODUCT_PRODUCT');
        $this->addSql('DROP TABLE top_product');
        $this->addSql('DROP TABLE chatbot_log');
        $this->addSql('DROP TABLE carousel_item');
        $this->addSql('ALTER TABLE category DROP display_order');
    }
}
