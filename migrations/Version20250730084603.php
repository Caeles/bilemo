<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250730084603 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product CHANGE brand brand VARCHAR(255) DEFAULT NULL, CHANGE model model VARCHAR(255) DEFAULT NULL, CHANGE color color VARCHAR(255) DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product CHANGE brand brand VARCHAR(255) DEFAULT \'NULL\', CHANGE model model VARCHAR(255) DEFAULT \'NULL\', CHANGE color color VARCHAR(255) DEFAULT \'NULL\', CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\' COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE `user` CHANGE roles roles LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`');
    }
}
