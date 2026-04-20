<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260219092149 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE stock_movement (id INT AUTO_INCREMENT NOT NULL, quantity_change INT NOT NULL, type VARCHAR(50) NOT NULL, reference_id INT DEFAULT NULL, created_at DATETIME NOT NULL, variant_id INT NOT NULL, INDEX IDX_BB1BC1B53B69A9AF (variant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE supplier (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, slug VARCHAR(150) NOT NULL, parent_company VARCHAR(150) DEFAULT NULL, default_lead_time_min INT DEFAULT NULL, default_lead_time_max INT DEFAULT NULL, is_active TINYINT NOT NULL, UNIQUE INDEX UNIQ_9B2A6C7E989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE variant_supply (id INT AUTO_INCREMENT NOT NULL, supplier_sku VARCHAR(100) DEFAULT NULL, is_available TINYINT NOT NULL, lead_time_min INT DEFAULT NULL, lead_time_max INT DEFAULT NULL, last_synced_at DATETIME DEFAULT NULL, variant_id INT NOT NULL, supplier_id INT NOT NULL, INDEX IDX_C743335A3B69A9AF (variant_id), INDEX IDX_C743335A2ADD6D8C (supplier_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE stock_movement ADD CONSTRAINT FK_BB1BC1B53B69A9AF FOREIGN KEY (variant_id) REFERENCES product_variant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE variant_supply ADD CONSTRAINT FK_C743335A3B69A9AF FOREIGN KEY (variant_id) REFERENCES product_variant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE variant_supply ADD CONSTRAINT FK_C743335A2ADD6D8C FOREIGN KEY (supplier_id) REFERENCES supplier (id)');
        $this->addSql('ALTER TABLE baktravel_profile DROP FOREIGN KEY `FK_40141D1E37B987D8`');
        $this->addSql('ALTER TABLE baktravel_profile DROP FOREIGN KEY `FK_40141D1E40F3B82`');
        $this->addSql('ALTER TABLE baktravel_profile DROP FOREIGN KEY `FK_40141D1EE33245BB`');
        $this->addSql('DROP TABLE baktravel_profile');
        $this->addSql('ALTER TABLE color DROP position');
        $this->addSql('DROP INDEX idx_status ON contact_message');
        $this->addSql('DROP INDEX idx_created_at ON contact_message');
        $this->addSql('ALTER TABLE contact_message CHANGE status status VARCHAR(20) NOT NULL');
        $this->addSql('DROP INDEX idx_transport ON faq');
        $this->addSql('DROP INDEX idx_active ON faq');
        $this->addSql('ALTER TABLE faq CHANGE question question LONGTEXT NOT NULL, CHANGE answer answer LONGTEXT NOT NULL, CHANGE position position INT NOT NULL, CHANGE is_active is_active TINYINT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE faq RENAME INDEX idx_airline TO IDX_E8FF75CC130D0C16');
        $this->addSql('ALTER TABLE product DROP allows_expandable_airline, DROP is_soft_luggage, CHANGE luggage_type luggage_type VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE product_variant ADD inventory_on_hand INT NOT NULL, ADD inventory_reserved INT NOT NULL');
        $this->addSql('ALTER TABLE travel_profile DROP created_at, DROP updated_at, CHANGE code code VARCHAR(255) NOT NULL, CHANGE story_intro story_intro LONGTEXT DEFAULT NULL, CHANGE story_block story_block LONGTEXT DEFAULT NULL, CHANGE tone_type tone_type VARCHAR(255) DEFAULT NULL, CHANGE priority_type priority_type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE travel_profile RENAME INDEX code TO UNIQ_40141D1E77153098');
        $this->addSql('ALTER TABLE travel_profile_trait CHANGE position position INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE travel_profile_trait RENAME INDEX idx_profile TO IDX_25718C95CCFA12B8');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE baktravel_profile (id INT AUTO_INCREMENT NOT NULL, recommended_volume_min INT DEFAULT NULL, recommended_volume_max INT DEFAULT NULL, priority_scope VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, recommended_wheels INT DEFAULT NULL, is_active TINYINT DEFAULT 1 NOT NULL, travel_type_id INT DEFAULT NULL, transport_mode_id INT DEFAULT NULL, duration_id INT DEFAULT NULL, INDEX IDX_40141D1E37B987D8 (duration_id), INDEX IDX_40141D1EE33245BB (transport_mode_id), INDEX IDX_40141D1E40F3B82 (travel_type_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE baktravel_profile ADD CONSTRAINT `FK_40141D1E37B987D8` FOREIGN KEY (duration_id) REFERENCES travel_duration (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE baktravel_profile ADD CONSTRAINT `FK_40141D1E40F3B82` FOREIGN KEY (travel_type_id) REFERENCES travel_type (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE baktravel_profile ADD CONSTRAINT `FK_40141D1EE33245BB` FOREIGN KEY (transport_mode_id) REFERENCES transport_mode (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE stock_movement DROP FOREIGN KEY FK_BB1BC1B53B69A9AF');
        $this->addSql('ALTER TABLE variant_supply DROP FOREIGN KEY FK_C743335A3B69A9AF');
        $this->addSql('ALTER TABLE variant_supply DROP FOREIGN KEY FK_C743335A2ADD6D8C');
        $this->addSql('DROP TABLE stock_movement');
        $this->addSql('DROP TABLE supplier');
        $this->addSql('DROP TABLE variant_supply');
        $this->addSql('ALTER TABLE color ADD position INT NOT NULL');
        $this->addSql('ALTER TABLE contact_message CHANGE status status VARCHAR(20) DEFAULT \'new\' NOT NULL');
        $this->addSql('CREATE INDEX idx_status ON contact_message (status)');
        $this->addSql('CREATE INDEX idx_created_at ON contact_message (created_at)');
        $this->addSql('ALTER TABLE faq CHANGE question question TEXT NOT NULL, CHANGE answer answer TEXT NOT NULL, CHANGE position position INT DEFAULT 0, CHANGE is_active is_active TINYINT DEFAULT 1, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX idx_transport ON faq (transport_type)');
        $this->addSql('CREATE INDEX idx_active ON faq (is_active)');
        $this->addSql('ALTER TABLE faq RENAME INDEX idx_e8ff75cc130d0c16 TO idx_airline');
        $this->addSql('ALTER TABLE product ADD allows_expandable_airline TINYINT DEFAULT 1 NOT NULL, ADD is_soft_luggage TINYINT DEFAULT 0 NOT NULL, CHANGE luggage_type luggage_type ENUM(\'hardcase\', \'softcase\', \'duffle\', \'backpack\', \'weekender\') DEFAULT NULL');
        $this->addSql('ALTER TABLE product_variant DROP inventory_on_hand, DROP inventory_reserved');
        $this->addSql('ALTER TABLE travel_profile ADD created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, ADD updated_at DATETIME DEFAULT NULL, CHANGE code code VARCHAR(50) NOT NULL, CHANGE story_intro story_intro TEXT DEFAULT NULL, CHANGE story_block story_block TEXT DEFAULT NULL, CHANGE tone_type tone_type VARCHAR(50) DEFAULT NULL, CHANGE priority_type priority_type VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE travel_profile RENAME INDEX uniq_40141d1e77153098 TO code');
        $this->addSql('ALTER TABLE travel_profile_trait CHANGE position position INT DEFAULT 0');
        $this->addSql('ALTER TABLE travel_profile_trait RENAME INDEX idx_25718c95ccfa12b8 TO IDX_PROFILE');
    }
}
