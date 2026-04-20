<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260326121104 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE brand ADD default_supplier_id INT DEFAULT NULL, CHANGE is_active is_active TINYINT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE brand ADD CONSTRAINT FK_1C52F958FE2E7E3A FOREIGN KEY (default_supplier_id) REFERENCES supplier (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_1C52F958FE2E7E3A ON brand (default_supplier_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE brand DROP FOREIGN KEY FK_1C52F958FE2E7E3A');
        $this->addSql('DROP INDEX IDX_1C52F958FE2E7E3A ON brand');
        $this->addSql('ALTER TABLE brand DROP default_supplier_id, CHANGE is_active is_active TINYINT NOT NULL');
    }
}
