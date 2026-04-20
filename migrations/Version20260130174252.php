<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260130174252 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category CHANGE allows_personal allows_personal TINYINT DEFAULT 0 NOT NULL, CHANGE allows_cabin allows_cabin TINYINT DEFAULT 0 NOT NULL, CHANGE allows_hold allows_hold TINYINT DEFAULT 0 NOT NULL, CHANGE transport_plane transport_plane TINYINT DEFAULT 1 NOT NULL, CHANGE transport_car transport_car TINYINT DEFAULT 1 NOT NULL, CHANGE transport_train transport_train TINYINT DEFAULT 1 NOT NULL, CHANGE transport_bus transport_bus TINYINT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE material CHANGE is_rigid is_rigid TINYINT DEFAULT 1 NOT NULL, CHANGE is_flexible is_flexible TINYINT DEFAULT 0 NOT NULL, CHANGE sustainability_score sustainability_score SMALLINT DEFAULT NULL, CHANGE notes notes VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE transport_category_map ADD is_active TINYINT NOT NULL, CHANGE baggage_type baggage_type VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE transport_category_map ADD CONSTRAINT FK_289DFCA112469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('CREATE INDEX IDX_289DFCA112469DE2 ON transport_category_map (category_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category CHANGE allows_personal allows_personal TINYINT DEFAULT 0, CHANGE allows_cabin allows_cabin TINYINT DEFAULT 0, CHANGE allows_hold allows_hold TINYINT DEFAULT 0, CHANGE transport_plane transport_plane TINYINT DEFAULT 1, CHANGE transport_car transport_car TINYINT DEFAULT 1, CHANGE transport_train transport_train TINYINT DEFAULT 1, CHANGE transport_bus transport_bus TINYINT DEFAULT 1');
        $this->addSql('ALTER TABLE material CHANGE is_rigid is_rigid TINYINT DEFAULT 1, CHANGE is_flexible is_flexible TINYINT DEFAULT 0, CHANGE sustainability_score sustainability_score TINYINT DEFAULT NULL, CHANGE notes notes VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE transport_category_map DROP FOREIGN KEY FK_289DFCA112469DE2');
        $this->addSql('DROP INDEX IDX_289DFCA112469DE2 ON transport_category_map');
        $this->addSql('ALTER TABLE transport_category_map DROP is_active, DROP category_id, CHANGE baggage_type baggage_type VARCHAR(50) NOT NULL');
    }
}
