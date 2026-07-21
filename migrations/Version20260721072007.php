<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260721072007 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE shop_order ADD shipment_email_sent_at DATETIME DEFAULT NULL, ADD shipment_email_sent_to VARCHAR(180) DEFAULT NULL, ADD shipment_email_last_error LONGTEXT DEFAULT NULL, ADD shipment_email_send_count INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE shop_order DROP shipment_email_sent_at, DROP shipment_email_sent_to, DROP shipment_email_last_error, DROP shipment_email_send_count');
    }
}
