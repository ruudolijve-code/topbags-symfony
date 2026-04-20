<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260228085704 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE order_item (id INT AUTO_INCREMENT NOT NULL, product_name VARCHAR(255) NOT NULL, variant_sku VARCHAR(255) NOT NULL, price DOUBLE PRECISION NOT NULL, qty INT NOT NULL, line_total DOUBLE PRECISION NOT NULL, order_id INT NOT NULL, INDEX IDX_52EA1F098D9F6D38 (order_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE shop_order (id INT AUTO_INCREMENT NOT NULL, order_number VARCHAR(255) NOT NULL, status VARCHAR(50) NOT NULL, subtotal DOUBLE PRECISION NOT NULL, shipping_cost DOUBLE PRECISION NOT NULL, total DOUBLE PRECISION NOT NULL, customer_email VARCHAR(255) NOT NULL, shipping_address JSON NOT NULL, mollie_payment_id VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_323FC9CA551F0F81 (order_number), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F098D9F6D38 FOREIGN KEY (order_id) REFERENCES shop_order (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F098D9F6D38');
        $this->addSql('DROP TABLE order_item');
        $this->addSql('DROP TABLE shop_order');
    }
}
