<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260713075205 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE magazine_article ADD related_category_id INT DEFAULT NULL, DROP related_category_slug');
        $this->addSql('ALTER TABLE magazine_article ADD CONSTRAINT FK_326E3DC0D9ADE366 FOREIGN KEY (related_category_id) REFERENCES category (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_326E3DC0D9ADE366 ON magazine_article (related_category_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE magazine_article DROP FOREIGN KEY FK_326E3DC0D9ADE366');
        $this->addSql('DROP INDEX IDX_326E3DC0D9ADE366 ON magazine_article');
        $this->addSql('ALTER TABLE magazine_article ADD related_category_slug VARCHAR(120) DEFAULT NULL, DROP related_category_id');
    }
}
