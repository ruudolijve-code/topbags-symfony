<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260601080929 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE travel_agency_landing_page (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(160) NOT NULL, slug VARCHAR(180) NOT NULL, city VARCHAR(120) NOT NULL, agency_type VARCHAR(80) DEFAULT NULL, is_active TINYINT DEFAULT 1 NOT NULL, seo_title VARCHAR(180) DEFAULT NULL, seo_description VARCHAR(255) DEFAULT NULL, h1 VARCHAR(180) DEFAULT NULL, intro_text LONGTEXT DEFAULT NULL, body_text LONGTEXT DEFAULT NULL, partner_text LONGTEXT DEFAULT NULL, position INT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_C53F87989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE travel_agency_landing_page');
    }
}
