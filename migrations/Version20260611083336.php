<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260611083336 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE chatbot_conversation (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) DEFAULT NULL, subject VARCHAR(255) DEFAULT NULL, status VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, contact_request_id INT DEFAULT NULL, INDEX IDX_764526E885C7E132 (contact_request_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE chatbot_message (id INT AUTO_INCREMENT NOT NULL, sender VARCHAR(20) NOT NULL, content LONGTEXT NOT NULL, question_key VARCHAR(80) DEFAULT NULL, metadata JSON DEFAULT NULL, created_at DATETIME NOT NULL, conversation_id INT NOT NULL, INDEX IDX_EDF1E8849AC0396 (conversation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE chatbot_conversation ADD CONSTRAINT FK_764526E885C7E132 FOREIGN KEY (contact_request_id) REFERENCES contact_request (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE chatbot_message ADD CONSTRAINT FK_EDF1E8849AC0396 FOREIGN KEY (conversation_id) REFERENCES chatbot_conversation (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE chatbot_conversation DROP FOREIGN KEY FK_764526E885C7E132');
        $this->addSql('ALTER TABLE chatbot_message DROP FOREIGN KEY FK_EDF1E8849AC0396');
        $this->addSql('DROP TABLE chatbot_conversation');
        $this->addSql('DROP TABLE chatbot_message');
    }
}
