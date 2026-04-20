<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260123132801 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_variant ADD normalized_color_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE product_variant ADD CONSTRAINT FK_209AA41DE5A451B0 FOREIGN KEY (normalized_color_id) REFERENCES color (id)');
        $this->addSql('CREATE INDEX IDX_209AA41DE5A451B0 ON product_variant (normalized_color_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_variant DROP FOREIGN KEY FK_209AA41DE5A451B0');
        $this->addSql('DROP INDEX IDX_209AA41DE5A451B0 ON product_variant');
        $this->addSql('ALTER TABLE product_variant DROP normalized_color_id');
    }
}
