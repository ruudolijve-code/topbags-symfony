<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260314134401 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_variant ADD allow_backorder TINYINT DEFAULT 0 NOT NULL, DROP inventory_on_hand, DROP inventory_reserved');
        $this->addSql('ALTER TABLE stock CHANGE quantity on_hand INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE stock_movement ADD reference_type VARCHAR(50) DEFAULT NULL, ADD note VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE supplier CHANGE is_active is_active TINYINT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE variant_supply DROP FOREIGN KEY `FK_C743335A2ADD6D8C`');
        $this->addSql('ALTER TABLE variant_supply ADD is_active TINYINT DEFAULT 1 NOT NULL, DROP is_available');
        $this->addSql('ALTER TABLE variant_supply ADD CONSTRAINT FK_C743335A2ADD6D8C FOREIGN KEY (supplier_id) REFERENCES supplier (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_variant ADD inventory_on_hand INT NOT NULL, ADD inventory_reserved INT NOT NULL, DROP allow_backorder');
        $this->addSql('ALTER TABLE stock CHANGE on_hand quantity INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE stock_movement DROP reference_type, DROP note');
        $this->addSql('ALTER TABLE supplier CHANGE is_active is_active TINYINT NOT NULL');
        $this->addSql('ALTER TABLE variant_supply DROP FOREIGN KEY FK_C743335A2ADD6D8C');
        $this->addSql('ALTER TABLE variant_supply ADD is_available TINYINT NOT NULL, DROP is_active');
        $this->addSql('ALTER TABLE variant_supply ADD CONSTRAINT `FK_C743335A2ADD6D8C` FOREIGN KEY (supplier_id) REFERENCES supplier (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
