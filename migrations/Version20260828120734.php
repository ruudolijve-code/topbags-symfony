<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260828120734 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Voegt een optionele subtitel toe aan Style Guide-vragen.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE style_guide_question
             ADD subtitle VARCHAR(255) DEFAULT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE style_guide_question
             DROP subtitle'
        );
    }
}