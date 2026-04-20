<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260228103559 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE shop_order DROP mollie_id, CHANGE subtotal subtotal NUMERIC(10, 2) NOT NULL, CHANGE shipping_cost shipping_cost NUMERIC(10, 2) NOT NULL, CHANGE total total NUMERIC(10, 2) NOT NULL, CHANGE mollie_payment_id mollie_payment_id VARCHAR(64) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE shop_order ADD mollie_id VARCHAR(64) DEFAULT NULL, CHANGE subtotal subtotal DOUBLE PRECISION NOT NULL, CHANGE shipping_cost shipping_cost DOUBLE PRECISION NOT NULL, CHANGE total total DOUBLE PRECISION NOT NULL, CHANGE mollie_payment_id mollie_payment_id VARCHAR(255) DEFAULT NULL');
    }
}
