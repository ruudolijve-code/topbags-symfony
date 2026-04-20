<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260328114946 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category CHANGE position position INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE shop_order ADD shipping_method VARCHAR(20) DEFAULT \'home\' NOT NULL, ADD pickup_location_code VARCHAR(120) DEFAULT NULL, ADD pickup_retail_network_id VARCHAR(120) DEFAULT NULL, ADD pickup_point_name VARCHAR(255) DEFAULT NULL, ADD pickup_street VARCHAR(255) DEFAULT NULL, ADD pickup_house_number VARCHAR(50) DEFAULT NULL, ADD pickup_postal_code VARCHAR(20) DEFAULT NULL, ADD pickup_city VARCHAR(120) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category CHANGE position position INT NOT NULL');
        $this->addSql('ALTER TABLE shop_order DROP shipping_method, DROP pickup_location_code, DROP pickup_retail_network_id, DROP pickup_point_name, DROP pickup_street, DROP pickup_house_number, DROP pickup_postal_code, DROP pickup_city');
    }
}
