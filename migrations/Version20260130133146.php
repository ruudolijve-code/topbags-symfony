<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260130133146 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE material (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(50) NOT NULL, density DOUBLE PRECISION DEFAULT NULL, UNIQUE INDEX UNIQ_7CBE75955E237E06 (name), UNIQUE INDEX UNIQ_7CBE7595989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE product DROP weight_per_liter');
        $this->addSql('ALTER TABLE transport_category_map DROP FOREIGN KEY `fk_transport_category`');
        $this->addSql('DROP INDEX idx_category ON transport_category_map');
        $this->addSql('DROP INDEX idx_transport ON transport_category_map');
        $this->addSql('ALTER TABLE transport_category_map ADD baggage_type VARCHAR(50) NOT NULL, ADD category_code VARCHAR(255) NOT NULL, DROP category_id, DROP bag_scope, DROP is_active, DROP created_at, CHANGE transport transport VARCHAR(50) NOT NULL, CHANGE priority priority INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE material');
        $this->addSql('ALTER TABLE product ADD weight_per_liter INT DEFAULT NULL');
        $this->addSql('ALTER TABLE transport_category_map ADD category_id INT NOT NULL, ADD bag_scope ENUM(\'personal\', \'cabin\', \'hold\', \'none\') DEFAULT \'none\', ADD is_active TINYINT DEFAULT 1 NOT NULL, ADD created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, DROP baggage_type, DROP category_code, CHANGE transport transport ENUM(\'car\', \'train\', \'bus\', \'plane\') NOT NULL, CHANGE priority priority TINYINT DEFAULT 100 NOT NULL');
        $this->addSql('ALTER TABLE transport_category_map ADD CONSTRAINT `fk_transport_category` FOREIGN KEY (category_id) REFERENCES category (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('CREATE INDEX idx_category ON transport_category_map (category_id)');
        $this->addSql('CREATE INDEX idx_transport ON transport_category_map (transport)');
    }
}
