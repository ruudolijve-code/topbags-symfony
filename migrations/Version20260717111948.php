<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260717111948 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add payment processing timestamps to shop orders';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE shop_order ADD paid_at DATETIME DEFAULT NULL, ADD stock_processed_at DATETIME DEFAULT NULL, ADD confirmation_email_sent_at DATETIME DEFAULT NULL, ADD admin_email_sent_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE shop_order DROP paid_at, DROP stock_processed_at, DROP confirmation_email_sent_at, DROP admin_email_sent_at');
    }
}
