<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260123115156 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE color (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, hex VARCHAR(7) DEFAULT NULL, swatch_type VARCHAR(20) DEFAULT \'solid\' NOT NULL, swatch_value VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_665648E9989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE image (id INT AUTO_INCREMENT NOT NULL, path VARCHAR(255) NOT NULL, position INT DEFAULT 0 NOT NULL, is_primary TINYINT DEFAULT 0 NOT NULL, product_variant_id INT NOT NULL, INDEX IDX_C53D045FA80EF684 (product_variant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, model_sku VARCHAR(50) NOT NULL, series VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, material VARCHAR(100) DEFAULT NULL, height_cm DOUBLE PRECISION DEFAULT NULL, width_cm DOUBLE PRECISION DEFAULT NULL, depth_cm DOUBLE PRECISION DEFAULT NULL, weight_kg DOUBLE PRECISION DEFAULT NULL, volume_l DOUBLE PRECISION DEFAULT NULL, expandable TINYINT DEFAULT 0 NOT NULL, expandable_volume_l DOUBLE PRECISION DEFAULT NULL, expandable_depth_cm DOUBLE PRECISION DEFAULT NULL, wheels_count INT DEFAULT NULL, luggage_type VARCHAR(20) NOT NULL, cabin_size TINYINT DEFAULT 0 NOT NULL, underseater TINYINT DEFAULT 0 NOT NULL, tsa_lock TINYINT DEFAULT 0 NOT NULL, closure_type VARCHAR(20) DEFAULT NULL, laptop_compartment TINYINT DEFAULT 0 NOT NULL, laptop_max_inch DOUBLE PRECISION DEFAULT NULL, is_active TINYINT DEFAULT 1 NOT NULL, brand_id INT NOT NULL, UNIQUE INDEX UNIQ_D34A04AD989D9B62 (slug), INDEX IDX_D34A04AD44F5D008 (brand_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE product_category (product_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_CDFC73564584665A (product_id), INDEX IDX_CDFC735612469DE2 (category_id), PRIMARY KEY (product_id, category_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE product_variant (id INT AUTO_INCREMENT NOT NULL, variant_sku VARCHAR(100) NOT NULL, ean VARCHAR(20) NOT NULL, price NUMERIC(10, 2) NOT NULL, is_master TINYINT DEFAULT 0 NOT NULL, is_active TINYINT DEFAULT 1 NOT NULL, supplier_color_name VARCHAR(255) DEFAULT NULL, supplier_color_code VARCHAR(100) DEFAULT NULL, product_id INT NOT NULL, color_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_209AA41D22168787 (variant_sku), INDEX IDX_209AA41D4584665A (product_id), INDEX IDX_209AA41D7ADA1FB5 (color_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE stock (id INT AUTO_INCREMENT NOT NULL, quantity INT DEFAULT 0 NOT NULL, reserved INT DEFAULT 0 NOT NULL, updated_at DATETIME NOT NULL, product_variant_id INT NOT NULL, UNIQUE INDEX UNIQ_4B365660A80EF684 (product_variant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE image ADD CONSTRAINT FK_C53D045FA80EF684 FOREIGN KEY (product_variant_id) REFERENCES product_variant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD44F5D008 FOREIGN KEY (brand_id) REFERENCES brand (id)');
        $this->addSql('ALTER TABLE product_category ADD CONSTRAINT FK_CDFC73564584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_category ADD CONSTRAINT FK_CDFC735612469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_variant ADD CONSTRAINT FK_209AA41D4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE product_variant ADD CONSTRAINT FK_209AA41D7ADA1FB5 FOREIGN KEY (color_id) REFERENCES color (id)');
        $this->addSql('ALTER TABLE stock ADD CONSTRAINT FK_4B365660A80EF684 FOREIGN KEY (product_variant_id) REFERENCES product_variant (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE image DROP FOREIGN KEY FK_C53D045FA80EF684');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD44F5D008');
        $this->addSql('ALTER TABLE product_category DROP FOREIGN KEY FK_CDFC73564584665A');
        $this->addSql('ALTER TABLE product_category DROP FOREIGN KEY FK_CDFC735612469DE2');
        $this->addSql('ALTER TABLE product_variant DROP FOREIGN KEY FK_209AA41D4584665A');
        $this->addSql('ALTER TABLE product_variant DROP FOREIGN KEY FK_209AA41D7ADA1FB5');
        $this->addSql('ALTER TABLE stock DROP FOREIGN KEY FK_4B365660A80EF684');
        $this->addSql('DROP TABLE color');
        $this->addSql('DROP TABLE image');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE product_category');
        $this->addSql('DROP TABLE product_variant');
        $this->addSql('DROP TABLE stock');
    }
}
