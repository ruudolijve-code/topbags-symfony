<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260130175348 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
{
    // ❌ GEEN NOT NULL afdwingen zolang data nog niet klopt
    $this->addSql('ALTER TABLE transport_category_map CHANGE category_id category_id INT DEFAULT NULL');
}

    public function down(Schema $schema): void
    {
       
    }
}
