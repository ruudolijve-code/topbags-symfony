<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260817142223 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE damage_report_image (id INT AUTO_INCREMENT NOT NULL, filename VARCHAR(255) NOT NULL, position INT DEFAULT 0 NOT NULL, damage_report_id INT NOT NULL, INDEX IDX_CD4844BA8AC13AA1 (damage_report_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE damage_report_image ADD CONSTRAINT FK_CD4844BA8AC13AA1 FOREIGN KEY (damage_report_id) REFERENCES damage_report (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE damage_report_image DROP FOREIGN KEY FK_CD4844BA8AC13AA1');
        $this->addSql('DROP TABLE damage_report_image');
    }
}
