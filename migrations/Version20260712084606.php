<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260712084606 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_magazine_article_context_published ON magazine_article');
        $this->addSql('DROP INDEX uniq_magazine_article_context_slug ON magazine_article');
        $this->addSql('ALTER TABLE magazine_article DROP context');
        $this->addSql('CREATE UNIQUE INDEX uniq_magazine_article_slug ON magazine_article (slug)');
    }
}
