<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260315220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create initial schema: user, category, ticket, comment tables with UUID primary keys';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE `user` (
            uuid BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\',
            email VARCHAR(180) NOT NULL,
            roles JSON NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email),
            PRIMARY KEY(uuid)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE category (
            uuid BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\',
            name VARCHAR(255) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(uuid)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE ticket (
            uuid BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\',
            client_uuid BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\',
            assigned_to_uuid BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\',
            category_uuid BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\',
            title VARCHAR(255) NOT NULL,
            description LONGTEXT NOT NULL,
            priority VARCHAR(20) NOT NULL,
            status VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_TICKET_CLIENT (client_uuid),
            INDEX IDX_TICKET_ASSIGNED (assigned_to_uuid),
            INDEX IDX_TICKET_CATEGORY (category_uuid),
            PRIMARY KEY(uuid)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE comment (
            uuid BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\',
            author_uuid BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\',
            ticket_uuid BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\',
            content LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_COMMENT_AUTHOR (author_uuid),
            INDEX IDX_COMMENT_TICKET (ticket_uuid),
            PRIMARY KEY(uuid)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_TICKET_CLIENT FOREIGN KEY (client_uuid) REFERENCES `user` (uuid)');
        $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_TICKET_ASSIGNED FOREIGN KEY (assigned_to_uuid) REFERENCES `user` (uuid)');
        $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_TICKET_CATEGORY FOREIGN KEY (category_uuid) REFERENCES category (uuid)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_COMMENT_AUTHOR FOREIGN KEY (author_uuid) REFERENCES `user` (uuid)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_COMMENT_TICKET FOREIGN KEY (ticket_uuid) REFERENCES ticket (uuid)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_COMMENT_TICKET');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_COMMENT_AUTHOR');
        $this->addSql('ALTER TABLE ticket DROP FOREIGN KEY FK_TICKET_CATEGORY');
        $this->addSql('ALTER TABLE ticket DROP FOREIGN KEY FK_TICKET_ASSIGNED');
        $this->addSql('ALTER TABLE ticket DROP FOREIGN KEY FK_TICKET_CLIENT');
        $this->addSql('DROP TABLE comment');
        $this->addSql('DROP TABLE ticket');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE `user`');
    }
}
