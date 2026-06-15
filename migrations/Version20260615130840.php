<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260615130840 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Voegt bouncegegevens toe aan nieuwsbriefinschrijvingen.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE newsletter_subscription
                ADD bounce_count INT DEFAULT 0 NOT NULL,
                ADD last_bounce_type VARCHAR(20) DEFAULT NULL,
                ADD last_bounce_reason LONGTEXT DEFAULT NULL,
                ADD last_bounced_at DATETIME DEFAULT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE newsletter_subscription
                DROP bounce_count,
                DROP last_bounce_type,
                DROP last_bounce_reason,
                DROP last_bounced_at'
        );
    }
}