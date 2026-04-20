<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260129124012 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE airline_baggage_rule ADD quantiti_cabin INT DEFAULT NULL');
        $this->addSql('DROP INDEX uniq_transport ON transport_advice');
        $this->addSql('ALTER TABLE transport_advice DROP created_at, DROP updated_at, CHANGE advice advice LONGTEXT NOT NULL, CHANGE extra_tip extra_tip LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE airline_baggage_rule DROP quantiti_cabin');
        $this->addSql('ALTER TABLE transport_advice ADD created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, ADD updated_at DATETIME DEFAULT NULL, CHANGE advice advice TEXT NOT NULL, CHANGE extra_tip extra_tip TEXT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_transport ON transport_advice (transport)');
    }
}
