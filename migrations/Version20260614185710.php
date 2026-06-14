<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260614185710 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE magazine_article (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, seo_title VARCHAR(255) DEFAULT NULL, seo_description LONGTEXT DEFAULT NULL, excerpt LONGTEXT DEFAULT NULL, content LONGTEXT NOT NULL, category VARCHAR(100) DEFAULT NULL, hero_image VARCHAR(255) DEFAULT NULL, is_published TINYINT NOT NULL, published_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, related_brand_slug VARCHAR(120) DEFAULT NULL, related_category_slug VARCHAR(120) DEFAULT NULL, UNIQUE INDEX uniq_magazine_article_slug (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE magazine_article');
    }
}
