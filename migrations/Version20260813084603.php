<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260813084603 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE style_guide_object_profile (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(100) NOT NULL, name VARCHAR(150) NOT NULL, width_cm DOUBLE PRECISION DEFAULT NULL, height_cm DOUBLE PRECISION DEFAULT NULL, depth_cm DOUBLE PRECISION DEFAULT NULL, volume_l DOUBLE PRECISION DEFAULT NULL, shape_type VARCHAR(30) NOT NULL, orientation VARCHAR(30) NOT NULL, requires_laptop_compartment TINYINT DEFAULT 0 NOT NULL, required_laptop_inch DOUBLE PRECISION DEFAULT NULL, requires_a4_fit TINYINT DEFAULT 0 NOT NULL, width_margin_cm DOUBLE PRECISION DEFAULT NULL, height_margin_cm DOUBLE PRECISION DEFAULT NULL, depth_margin_cm DOUBLE PRECISION DEFAULT NULL, bulk_factor DOUBLE PRECISION DEFAULT 1 NOT NULL, is_active TINYINT DEFAULT 1 NOT NULL, UNIQUE INDEX UNIQ_430EDA3977153098 (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE style_guide_question CHANGE help_text help_text LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE style_guide_object_profile');
        $this->addSql('ALTER TABLE style_guide_question CHANGE help_text help_text TEXT DEFAULT NULL');
    }
}
