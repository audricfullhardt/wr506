<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251017124313 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category CHANGE id id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE comment CHANGE id id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', CHANGE ticket_id ticket_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE ticket CHANGE id id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', CHANGE client_id client_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', CHANGE assigned_to_id assigned_to_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', CHANGE category_id category_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE user CHANGE id id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `user` CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE comment CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE ticket_id ticket_id INT NOT NULL');
        $this->addSql('ALTER TABLE category CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE ticket CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE client_id client_id INT NOT NULL, CHANGE assigned_to_id assigned_to_id INT DEFAULT NULL, CHANGE category_id category_id INT NOT NULL');
    }
}
