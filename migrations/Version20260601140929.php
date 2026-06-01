<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260601140929 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE travel_agency_landing_page ADD show_coupon_block TINYINT DEFAULT 1 NOT NULL, ADD coupon_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE travel_agency_landing_page ADD CONSTRAINT FK_C53F8766C5951B FOREIGN KEY (coupon_id) REFERENCES coupon (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_C53F8766C5951B ON travel_agency_landing_page (coupon_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE travel_agency_landing_page DROP FOREIGN KEY FK_C53F8766C5951B');
        $this->addSql('DROP INDEX IDX_C53F8766C5951B ON travel_agency_landing_page');
        $this->addSql('ALTER TABLE travel_agency_landing_page DROP show_coupon_block, DROP coupon_id');
    }
}
