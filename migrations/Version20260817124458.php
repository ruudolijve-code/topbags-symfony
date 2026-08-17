<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260817124458 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE damage_report (id INT AUTO_INCREMENT NOT NULL, report_number VARCHAR(50) NOT NULL, status VARCHAR(20) NOT NULL, report_date DATE NOT NULL, customer_name VARCHAR(255) NOT NULL, customer_address VARCHAR(255) DEFAULT NULL, customer_postal_code VARCHAR(20) DEFAULT NULL, customer_city VARCHAR(150) DEFAULT NULL, customer_email VARCHAR(255) DEFAULT NULL, customer_phone VARCHAR(50) DEFAULT NULL, brand VARCHAR(150) DEFAULT NULL, series VARCHAR(150) DEFAULT NULL, model VARCHAR(150) DEFAULT NULL, color VARCHAR(100) DEFAULT NULL, dimensions VARCHAR(100) DEFAULT NULL, estimated_purchase_date VARCHAR(100) DEFAULT NULL, estimated_purchase_price DOUBLE PRECISION DEFAULT NULL, airline VARCHAR(150) DEFAULT NULL, flight_number VARCHAR(50) DEFAULT NULL, flight_date DATE DEFAULT NULL, airport VARCHAR(150) DEFAULT NULL, pir_number VARCHAR(100) DEFAULT NULL, damage_description LONGTEXT NOT NULL, assessment VARCHAR(30) DEFAULT NULL, technical_assessment LONGTEXT DEFAULT NULL, conclusion LONGTEXT DEFAULT NULL, repair_costs DOUBLE PRECISION DEFAULT NULL, replacement_value DOUBLE PRECISION DEFAULT NULL, internal_notes LONGTEXT DEFAULT NULL, assessor_name VARCHAR(150) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_D7D56F97BF63CACB (report_number), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE damage_report');
    }
}
