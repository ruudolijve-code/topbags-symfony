<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260817140351 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE damage_report ADD order_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE damage_report ADD CONSTRAINT FK_D7D56F978D9F6D38 FOREIGN KEY (order_id) REFERENCES shop_order (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_D7D56F978D9F6D38 ON damage_report (order_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE damage_report DROP FOREIGN KEY FK_D7D56F978D9F6D38');
        $this->addSql('DROP INDEX IDX_D7D56F978D9F6D38 ON damage_report');
        $this->addSql('ALTER TABLE damage_report DROP order_id');
    }
}
