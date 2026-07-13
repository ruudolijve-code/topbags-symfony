<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260713064032 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE magazine_article_brand (magazine_article_id INT NOT NULL, brand_id INT NOT NULL, INDEX IDX_2225C689370C57A3 (magazine_article_id), INDEX IDX_2225C68944F5D008 (brand_id), PRIMARY KEY (magazine_article_id, brand_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE magazine_article_brand ADD CONSTRAINT FK_2225C689370C57A3 FOREIGN KEY (magazine_article_id) REFERENCES magazine_article (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE magazine_article_brand ADD CONSTRAINT FK_2225C68944F5D008 FOREIGN KEY (brand_id) REFERENCES brand (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE magazine_article CHANGE related_brand_slug related_brand_slug VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE magazine_article_brand DROP FOREIGN KEY FK_2225C689370C57A3');
        $this->addSql('ALTER TABLE magazine_article_brand DROP FOREIGN KEY FK_2225C68944F5D008');
        $this->addSql('DROP TABLE magazine_article_brand');
        $this->addSql('ALTER TABLE magazine_article CHANGE related_brand_slug related_brand_slug VARCHAR(120) DEFAULT NULL');
    }
}
