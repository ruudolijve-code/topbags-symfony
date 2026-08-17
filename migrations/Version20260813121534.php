<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260813121534 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Voegt meerdere productcontexten per product toe met utf8mb4_unicode_ci collation.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE product_context (
                id INT AUTO_INCREMENT NOT NULL,
                context VARCHAR(20) NOT NULL,
                position INT DEFAULT 0 NOT NULL,
                is_active TINYINT DEFAULT 1 NOT NULL,
                product_id INT NOT NULL,
                INDEX IDX_33849ECE4584665A (product_id),
                UNIQUE INDEX uniq_product_context (product_id, context),
                PRIMARY KEY (id)
            )
            DEFAULT CHARACTER SET utf8mb4
            COLLATE utf8mb4_unicode_ci'
        );

        $this->addSql(
            'ALTER TABLE product_context
             ADD CONSTRAINT FK_33849ECE4584665A
             FOREIGN KEY (product_id)
             REFERENCES product (id)
             ON DELETE CASCADE'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE product_context
             DROP FOREIGN KEY FK_33849ECE4584665A'
        );

        $this->addSql(
            'DROP TABLE product_context'
        );
    }
}