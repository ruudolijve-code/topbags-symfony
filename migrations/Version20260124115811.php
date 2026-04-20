<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260124115811 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_variant DROP FOREIGN KEY `fk_product_variant_color`');
        $this->addSql('ALTER TABLE product_variant DROP FOREIGN KEY `fk_product_variant_normalized_color`');
        $this->addSql('ALTER TABLE product_variant ADD supplier_color_slug VARCHAR(120) NOT NULL, CHANGE supplier_color_name supplier_color_name VARCHAR(100) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_variant DROP supplier_color_slug, CHANGE supplier_color_name supplier_color_name VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE product_variant ADD CONSTRAINT `fk_product_variant_color` FOREIGN KEY (color_id) REFERENCES color (id) ON UPDATE NO ACTION ON DELETE SET NULL');
        $this->addSql('ALTER TABLE product_variant ADD CONSTRAINT `fk_product_variant_normalized_color` FOREIGN KEY (normalized_color_id) REFERENCES color (id) ON UPDATE NO ACTION ON DELETE SET NULL');
    }
}
