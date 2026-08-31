<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260831150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Maakt materiaalfamilies beschikbaar als doel voor Style Guide-affiniteiten.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE style_guide_affinity ADD material_family_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE style_guide_affinity ADD CONSTRAINT FK_STYLE_GUIDE_AFFINITY_MATERIAL_FAMILY FOREIGN KEY (material_family_id) REFERENCES material_family (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_STYLE_GUIDE_AFFINITY_MATERIAL_FAMILY ON style_guide_affinity (material_family_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE style_guide_affinity DROP FOREIGN KEY FK_STYLE_GUIDE_AFFINITY_MATERIAL_FAMILY');
        $this->addSql('DROP INDEX IDX_STYLE_GUIDE_AFFINITY_MATERIAL_FAMILY ON style_guide_affinity');
        $this->addSql('ALTER TABLE style_guide_affinity DROP material_family_id');
    }
}
