<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260712085806 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

   public function up(Schema $schema): void
{
    $this->addSql(
        "ALTER TABLE magazine_article
         ADD context VARCHAR(20) DEFAULT 'shop' NOT NULL"
    );

    $this->addSql(
        'DROP INDEX uniq_magazine_article_slug ON magazine_article'
    );

    $this->addSql(
        'CREATE UNIQUE INDEX uniq_magazine_article_context_slug
         ON magazine_article (context, slug)'
    );

    $this->addSql(
        'CREATE INDEX idx_magazine_article_context_published
         ON magazine_article (context, is_published, published_at)'
    );
}

public function down(Schema $schema): void
{
    $this->addSql(
        'DROP INDEX idx_magazine_article_context_published ON magazine_article'
    );

    $this->addSql(
        'DROP INDEX uniq_magazine_article_context_slug ON magazine_article'
    );

    $this->addSql(
        'CREATE UNIQUE INDEX uniq_magazine_article_slug
         ON magazine_article (slug)'
    );

    $this->addSql(
        'ALTER TABLE magazine_article DROP context'
    );
}
}
