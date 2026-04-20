<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260126143717 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE airline (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, is_active TINYINT DEFAULT 1 NOT NULL, UNIQUE INDEX UNIQ_EC141EF8989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE airline_baggage_rule (id INT AUTO_INCREMENT NOT NULL, baggage_type VARCHAR(20) NOT NULL, dimension_type VARCHAR(20) NOT NULL, max_height_cm INT DEFAULT NULL, max_width_cm INT DEFAULT NULL, max_depth_cm INT DEFAULT NULL, max_linear_cm INT DEFAULT NULL, max_weight_kg DOUBLE PRECISION DEFAULT NULL, is_active TINYINT DEFAULT 1 NOT NULL, airline_id INT NOT NULL, ticket_type_id INT NOT NULL, INDEX IDX_3E77650A130D0C16 (airline_id), INDEX IDX_3E77650AC980D5C1 (ticket_type_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE airline_ticket_type (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(50) NOT NULL, label VARCHAR(100) NOT NULL, is_active TINYINT DEFAULT 1 NOT NULL, airline_id INT NOT NULL, INDEX IDX_ACFED4A5130D0C16 (airline_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE transport_mode (id INT AUTO_INCREMENT NOT NULL, slug VARCHAR(30) NOT NULL, name VARCHAR(50) NOT NULL, is_active TINYINT DEFAULT 1 NOT NULL, UNIQUE INDEX UNIQ_4A1A102E989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE travel_duration (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(30) NOT NULL, min_days INT NOT NULL, max_days INT NOT NULL, is_active TINYINT DEFAULT 1 NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE travel_profile (id INT AUTO_INCREMENT NOT NULL, recommended_volume_min INT DEFAULT NULL, recommended_volume_max INT DEFAULT NULL, priority_scope VARCHAR(255) DEFAULT NULL, recommended_wheels INT DEFAULT NULL, is_active TINYINT DEFAULT 1 NOT NULL, travel_type_id INT DEFAULT NULL, transport_mode_id INT DEFAULT NULL, duration_id INT DEFAULT NULL, INDEX IDX_40141D1E40F3B82 (travel_type_id), INDEX IDX_40141D1EE33245BB (transport_mode_id), INDEX IDX_40141D1E37B987D8 (duration_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE travel_type (id INT AUTO_INCREMENT NOT NULL, slug VARCHAR(50) NOT NULL, name VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, is_active TINYINT DEFAULT 1 NOT NULL, UNIQUE INDEX UNIQ_4786B484989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE airline_baggage_rule ADD CONSTRAINT FK_3E77650A130D0C16 FOREIGN KEY (airline_id) REFERENCES airline (id)');
        $this->addSql('ALTER TABLE airline_baggage_rule ADD CONSTRAINT FK_3E77650AC980D5C1 FOREIGN KEY (ticket_type_id) REFERENCES airline_ticket_type (id)');
        $this->addSql('ALTER TABLE airline_ticket_type ADD CONSTRAINT FK_ACFED4A5130D0C16 FOREIGN KEY (airline_id) REFERENCES airline (id)');
        $this->addSql('ALTER TABLE travel_profile ADD CONSTRAINT FK_40141D1E40F3B82 FOREIGN KEY (travel_type_id) REFERENCES travel_type (id)');
        $this->addSql('ALTER TABLE travel_profile ADD CONSTRAINT FK_40141D1EE33245BB FOREIGN KEY (transport_mode_id) REFERENCES transport_mode (id)');
        $this->addSql('ALTER TABLE travel_profile ADD CONSTRAINT FK_40141D1E37B987D8 FOREIGN KEY (duration_id) REFERENCES travel_duration (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE airline_baggage_rule DROP FOREIGN KEY FK_3E77650A130D0C16');
        $this->addSql('ALTER TABLE airline_baggage_rule DROP FOREIGN KEY FK_3E77650AC980D5C1');
        $this->addSql('ALTER TABLE airline_ticket_type DROP FOREIGN KEY FK_ACFED4A5130D0C16');
        $this->addSql('ALTER TABLE travel_profile DROP FOREIGN KEY FK_40141D1E40F3B82');
        $this->addSql('ALTER TABLE travel_profile DROP FOREIGN KEY FK_40141D1EE33245BB');
        $this->addSql('ALTER TABLE travel_profile DROP FOREIGN KEY FK_40141D1E37B987D8');
        $this->addSql('DROP TABLE airline');
        $this->addSql('DROP TABLE airline_baggage_rule');
        $this->addSql('DROP TABLE airline_ticket_type');
        $this->addSql('DROP TABLE transport_mode');
        $this->addSql('DROP TABLE travel_duration');
        $this->addSql('DROP TABLE travel_profile');
        $this->addSql('DROP TABLE travel_type');
    }
}
