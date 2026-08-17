<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260813130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Kopieert alle bestaande productcontexten naar de nieuwe multi-context tabel.';
    }

    public function up(Schema $schema): void
    {
        /*
         * Elk bestaand product krijgt minimaal zijn huidige legacy-context.
         *
         * Werkt zowel lokaal als op productie, ongeacht hoeveel producten
         * daar aanwezig zijn.
         */
        $this->addSql(
            'INSERT INTO product_context (
                product_id,
                context,
                position,
                is_active
            )
            SELECT
                p.id,
                p.product_context,
                0,
                1
            FROM product p
            WHERE p.product_context IS NOT NULL
              AND NOT EXISTS (
                  SELECT 1
                  FROM product_context pc
                  WHERE pc.product_id = p.id
                    AND pc.context = p.product_context
              )'
        );
    }

    public function down(Schema $schema): void
    {
        /*
         * Alleen records verwijderen die overeenkomen met de legacy-context.
         * Eventueel later handmatig toegevoegde tweede contexten blijven staan.
         */
        $this->addSql(
            'DELETE pc
             FROM product_context pc
             INNER JOIN product p
                 ON p.id = pc.product_id
             WHERE pc.context = p.product_context'
        );
    }
}