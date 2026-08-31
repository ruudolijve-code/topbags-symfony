<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260831120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Hernoemt kwaliteitskolommen naar marktpositionering en voegt materiaalfamilies toe.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE material_family (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQ_MATERIAL_FAMILY_NAME (name), UNIQUE INDEX UNIQ_MATERIAL_FAMILY_SLUG (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE material ADD family_id INT DEFAULT NULL, CHANGE quality_modifier market_position_modifier INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE material ADD CONSTRAINT FK_MATERIAL_FAMILY FOREIGN KEY (family_id) REFERENCES material_family (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_MATERIAL_FAMILY ON material (family_id)');
        $this->addSql('ALTER TABLE brand CHANGE brand_quality_score market_position INT DEFAULT 50 NOT NULL');
        $this->addSql('ALTER TABLE product CHANGE quality_score_override market_position_override INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE material DROP FOREIGN KEY FK_MATERIAL_FAMILY');
        $this->addSql('DROP INDEX IDX_MATERIAL_FAMILY ON material');
        $this->addSql('ALTER TABLE material DROP family_id, CHANGE market_position_modifier quality_modifier INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE brand CHANGE market_position brand_quality_score INT DEFAULT 50 NOT NULL');
        $this->addSql('ALTER TABLE product CHANGE market_position_override quality_score_override INT DEFAULT NULL');
        $this->addSql('DROP TABLE material_family');
    }
}
