<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260602083110 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE airline ADD seo_title VARCHAR(255) DEFAULT NULL, ADD seo_description LONGTEXT DEFAULT NULL, ADD seo_h1 VARCHAR(255) DEFAULT NULL, ADD seo_intro LONGTEXT DEFAULT NULL, ADD canonical_url VARCHAR(255) DEFAULT NULL, ADD is_indexable TINYINT DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE airline DROP seo_title, DROP seo_description, DROP seo_h1, DROP seo_intro, DROP canonical_url, DROP is_indexable');
    }
}
