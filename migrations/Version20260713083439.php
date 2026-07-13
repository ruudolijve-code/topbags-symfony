<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;

final class Version20260713083439 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Zet gerelateerde magazinecategorieën om naar een ManyToMany-relatie en verwijdert het oude merk-slugveld.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            'Deze migratie kan alleen veilig op MySQL worden uitgevoerd.'
        );

        /*
         * 1. Koppeltabel aanmaken wanneer die nog niet bestaat.
         */
        if (!$this->tableExists('magazine_article_category')) {
            $this->addSql(
                'CREATE TABLE magazine_article_category (
                    magazine_article_id INT NOT NULL,
                    category_id INT NOT NULL,
                    INDEX IDX_1CB8885B370C57A3 (magazine_article_id),
                    INDEX IDX_1CB8885B12469DE2 (category_id),
                    PRIMARY KEY (magazine_article_id, category_id)
                ) DEFAULT CHARACTER SET utf8mb4
                  COLLATE `utf8mb4_unicode_ci`
                  ENGINE = InnoDB'
            );

            $this->addSql(
                'ALTER TABLE magazine_article_category
                 ADD CONSTRAINT FK_1CB8885B370C57A3
                 FOREIGN KEY (magazine_article_id)
                 REFERENCES magazine_article (id)
                 ON DELETE CASCADE'
            );

            $this->addSql(
                'ALTER TABLE magazine_article_category
                 ADD CONSTRAINT FK_1CB8885B12469DE2
                 FOREIGN KEY (category_id)
                 REFERENCES category (id)
                 ON DELETE CASCADE'
            );
        }

        /*
         * 2a. Lokaal kan de eerdere ManyToOne-migratie al zijn uitgevoerd.
         * Zet related_category_id dan over naar de koppeltabel.
         */
        if ($this->columnExists('magazine_article', 'related_category_id')) {
            $this->addSql(
                'INSERT IGNORE INTO magazine_article_category (
                    magazine_article_id,
                    category_id
                 )
                 SELECT
                    id,
                    related_category_id
                 FROM magazine_article
                 WHERE related_category_id IS NOT NULL'
            );
        }

        /*
         * 2b. Productie kan nog het oorspronkelijke slugveld hebben.
         * Zet ook die gegevens veilig over.
         */
        if ($this->columnExists('magazine_article', 'related_category_slug')) {
            $this->addSql(
                'INSERT IGNORE INTO magazine_article_category (
                    magazine_article_id,
                    category_id
                 )
                 SELECT
                    ma.id,
                    c.id
                 FROM magazine_article ma
                 INNER JOIN category c
                    ON c.slug = ma.related_category_slug
                 WHERE ma.related_category_slug IS NOT NULL
                   AND ma.related_category_slug <> \'\''
            );
        }

        /*
         * 3. Foreign keys op related_category_id eerst verwijderen.
         */
        if ($this->columnExists('magazine_article', 'related_category_id')) {
            foreach ($this->foreignKeysForColumn(
                'magazine_article',
                'related_category_id'
            ) as $foreignKey) {
                $this->addSql(sprintf(
                    'ALTER TABLE magazine_article DROP FOREIGN KEY `%s`',
                    str_replace('`', '``', $foreignKey)
                ));
            }

            $this->addSql(
                'ALTER TABLE magazine_article DROP related_category_id'
            );
        }

        /*
         * 4. Oude tekstvelden verwijderen.
         */
        if ($this->columnExists('magazine_article', 'related_category_slug')) {
            $this->addSql(
                'ALTER TABLE magazine_article DROP related_category_slug'
            );
        }

        if ($this->columnExists('magazine_article', 'related_brand_slug')) {
            $this->addSql(
                'ALTER TABLE magazine_article DROP related_brand_slug'
            );
        }
    }

    public function down(Schema $schema): void
    {
        /*
         * Het oude model ondersteunde maar één categorie.
         * Bij een rollback wordt daarom de eerste gekoppelde categorie
         * per artikel teruggezet.
         */
        $this->addSql(
            'ALTER TABLE magazine_article
             ADD related_brand_slug VARCHAR(255) DEFAULT NULL,
             ADD related_category_id INT DEFAULT NULL'
        );

        $this->addSql(
            'UPDATE magazine_article ma
             INNER JOIN (
                SELECT
                    magazine_article_id,
                    MIN(category_id) AS category_id
                FROM magazine_article_category
                GROUP BY magazine_article_id
             ) mac
                ON mac.magazine_article_id = ma.id
             SET ma.related_category_id = mac.category_id'
        );

        $this->addSql(
            'CREATE INDEX IDX_326E3DC0D9ADE366
             ON magazine_article (related_category_id)'
        );

        $this->addSql(
            'ALTER TABLE magazine_article
             ADD CONSTRAINT FK_326E3DC0D9ADE366
             FOREIGN KEY (related_category_id)
             REFERENCES category (id)
             ON DELETE SET NULL'
        );

        $this->addSql(
            'DROP TABLE magazine_article_category'
        );
    }

    private function tableExists(string $tableName): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT COUNT(*)
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :tableName',
            [
                'tableName' => $tableName,
            ]
        );
    }

    private function columnExists(
        string $tableName,
        string $columnName
    ): bool {
        return (bool) $this->connection->fetchOne(
            'SELECT COUNT(*)
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :tableName
               AND COLUMN_NAME = :columnName',
            [
                'tableName' => $tableName,
                'columnName' => $columnName,
            ]
        );
    }

    /**
     * @return list<string>
     */
    private function foreignKeysForColumn(
        string $tableName,
        string $columnName
    ): array {
        return $this->connection->fetchFirstColumn(
            'SELECT CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :tableName
               AND COLUMN_NAME = :columnName
               AND REFERENCED_TABLE_NAME IS NOT NULL',
            [
                'tableName' => $tableName,
                'columnName' => $columnName,
            ]
        );
    }
}