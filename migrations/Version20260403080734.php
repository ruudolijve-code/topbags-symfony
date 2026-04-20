<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260403080734 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE customer_user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(100) DEFAULT NULL, last_name VARCHAR(100) DEFAULT NULL, phone VARCHAR(30) DEFAULT NULL, is_active TINYINT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_D902723EE7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE shop_order ADD customer_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE shop_order ADD CONSTRAINT FK_323FC9CA9395C3F3 FOREIGN KEY (customer_id) REFERENCES customer_user (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_323FC9CA9395C3F3 ON shop_order (customer_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE customer_user');
        $this->addSql('ALTER TABLE shop_order DROP FOREIGN KEY FK_323FC9CA9395C3F3');
        $this->addSql('DROP INDEX IDX_323FC9CA9395C3F3 ON shop_order');
        $this->addSql('ALTER TABLE shop_order DROP customer_id');
    }
}
