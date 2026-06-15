<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260615134513 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE newsletter_delivery (id INT AUTO_INCREMENT NOT NULL, recipient_email VARCHAR(180) NOT NULL, delivery_token VARCHAR(64) NOT NULL, status VARCHAR(30) NOT NULL, message_id VARCHAR(255) DEFAULT NULL, smtp_accepted_at DATETIME DEFAULT NULL, direct_failed_at DATETIME DEFAULT NULL, direct_failure_reason LONGTEXT DEFAULT NULL, bounce_type VARCHAR(20) DEFAULT NULL, bounce_reason LONGTEXT DEFAULT NULL, bounced_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, campaign_id INT NOT NULL, subscription_id INT DEFAULT NULL, INDEX IDX_EA832A7AF639F774 (campaign_id), INDEX IDX_EA832A7A9A1887DC (subscription_id), INDEX idx_newsletter_delivery_campaign_status (campaign_id, status), INDEX idx_newsletter_delivery_recipient_email (recipient_email), INDEX idx_newsletter_delivery_message_id (message_id), UNIQUE INDEX uniq_newsletter_delivery_token (delivery_token), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE newsletter_delivery ADD CONSTRAINT FK_EA832A7AF639F774 FOREIGN KEY (campaign_id) REFERENCES newsletter_campaign (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE newsletter_delivery ADD CONSTRAINT FK_EA832A7A9A1887DC FOREIGN KEY (subscription_id) REFERENCES newsletter_subscription (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE newsletter_delivery DROP FOREIGN KEY FK_EA832A7AF639F774');
        $this->addSql('ALTER TABLE newsletter_delivery DROP FOREIGN KEY FK_EA832A7A9A1887DC');
        $this->addSql('DROP TABLE newsletter_delivery');
    }
}
