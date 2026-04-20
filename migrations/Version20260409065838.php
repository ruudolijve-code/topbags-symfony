<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260409065838 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE shop_order ADD store_pickup_name VARCHAR(255) DEFAULT NULL, ADD store_pickup_street VARCHAR(255) DEFAULT NULL, ADD store_pickup_postal_code VARCHAR(20) DEFAULT NULL, ADD store_pickup_city VARCHAR(120) DEFAULT NULL, ADD store_pickup_country VARCHAR(10) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE shop_order DROP store_pickup_name, DROP store_pickup_street, DROP store_pickup_postal_code, DROP store_pickup_city, DROP store_pickup_country');
    }
}
