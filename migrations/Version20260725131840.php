<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260725131840 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add sizes to product variants';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE product_size (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, slug VARCHAR(50) NOT NULL, code VARCHAR(30) DEFAULT NULL, sort_order INT DEFAULT 0 NOT NULL, is_active TINYINT DEFAULT 1 NOT NULL, UNIQUE INDEX uniq_size_slug (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE product_variant ADD size_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE product_variant ADD CONSTRAINT FK_209AA41D498DA827 FOREIGN KEY (size_id) REFERENCES product_size (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_209AA41D498DA827 ON product_variant (size_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE product_variant DROP FOREIGN KEY FK_209AA41D498DA827');
        $this->addSql('DROP INDEX IDX_209AA41D498DA827 ON product_variant' );
        $this->addSql('ALTER TABLE product_variant DROP size_id');
        $this->addSql(
            'DROP TABLE product_size'
        );
    }
}
