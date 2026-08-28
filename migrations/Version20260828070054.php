<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260828070054 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE brand ADD quality_position INT DEFAULT 50 NOT NULL');
        $this->addSql('ALTER TABLE material ADD quality_modifier INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE product ADD quality_score_override INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE brand DROP quality_position');
        $this->addSql('ALTER TABLE material DROP quality_modifier');
        $this->addSql('ALTER TABLE product DROP quality_score_override');
    }
}
