<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260520060133 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE newsletter_campaign (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(180) NOT NULL, subject VARCHAR(180) NOT NULL, preheader VARCHAR(255) DEFAULT NULL, html_body LONGTEXT NOT NULL, status VARCHAR(30) NOT NULL, recipient_count INT DEFAULT 0 NOT NULL, sent_count INT DEFAULT 0 NOT NULL, failed_count INT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, sent_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE newsletter_send_log (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(30) NOT NULL, error_message LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, sent_at DATETIME DEFAULT NULL, campaign_id INT NOT NULL, subscription_id INT NOT NULL, INDEX IDX_FE2DFDB6F639F774 (campaign_id), INDEX IDX_FE2DFDB69A1887DC (subscription_id), UNIQUE INDEX uniq_newsletter_campaign_subscription (campaign_id, subscription_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE newsletter_send_log ADD CONSTRAINT FK_FE2DFDB6F639F774 FOREIGN KEY (campaign_id) REFERENCES newsletter_campaign (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE newsletter_send_log ADD CONSTRAINT FK_FE2DFDB69A1887DC FOREIGN KEY (subscription_id) REFERENCES newsletter_subscription (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE newsletter_send_log DROP FOREIGN KEY FK_FE2DFDB6F639F774');
        $this->addSql('ALTER TABLE newsletter_send_log DROP FOREIGN KEY FK_FE2DFDB69A1887DC');
        $this->addSql('DROP TABLE newsletter_campaign');
        $this->addSql('DROP TABLE newsletter_send_log');
    }
}
