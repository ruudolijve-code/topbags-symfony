<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260130133443 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product ADD material_id INT DEFAULT NULL, DROP material');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04ADE308AC6F FOREIGN KEY (material_id) REFERENCES material (id)');
        $this->addSql('CREATE INDEX IDX_D34A04ADE308AC6F ON product (material_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    $this->addSql("
    ALTER TABLE product
    ADD COLUMN weight_per_liter INT
    GENERATED ALWAYS AS ((weight_kg * 1000) / NULLIF(volume_l, 0)) STORED
");
}
}