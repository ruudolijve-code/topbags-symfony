<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260828103009 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Hernoemt quality_position naar brand_quality_score.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE brand CHANGE quality_position brand_quality_score INT DEFAULT 50 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE brand CHANGE brand_quality_score quality_position INT DEFAULT 50 NOT NULL');
    }
}
