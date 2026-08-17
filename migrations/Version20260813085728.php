<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260813085728 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Voegt objectprofiel-koppelingen toe voor de Stijlgids.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE style_guide_answer_object_profile (
                id INT AUTO_INCREMENT NOT NULL,
                weight INT DEFAULT 100 NOT NULL,
                is_active TINYINT DEFAULT 1 NOT NULL,
                answer_id INT NOT NULL,
                object_profile_id INT NOT NULL,
                INDEX IDX_BAE542CAAA334807 (answer_id),
                INDEX IDX_BAE542CABF65BB04 (object_profile_id),
                UNIQUE INDEX uniq_style_guide_answer_object_profile (answer_id, object_profile_id),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4'
        );

        $this->addSql(
            'ALTER TABLE style_guide_answer_object_profile
             ADD CONSTRAINT FK_BAE542CAAA334807
             FOREIGN KEY (answer_id)
             REFERENCES style_guide_answer (id)
             ON DELETE CASCADE'
        );

        $this->addSql(
            'ALTER TABLE style_guide_answer_object_profile
             ADD CONSTRAINT FK_BAE542CABF65BB04
             FOREIGN KEY (object_profile_id)
             REFERENCES style_guide_object_profile (id)
             ON DELETE CASCADE'
        );

        $this->addSql(
            'ALTER TABLE style_guide_object_profile
             CHANGE bulk_factor bulk_factor DOUBLE PRECISION DEFAULT 1 NOT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE style_guide_answer_object_profile
             DROP FOREIGN KEY FK_BAE542CAAA334807'
        );

        $this->addSql(
            'ALTER TABLE style_guide_answer_object_profile
             DROP FOREIGN KEY FK_BAE542CABF65BB04'
        );

        $this->addSql(
            'DROP TABLE style_guide_answer_object_profile'
        );

        $this->addSql(
            'ALTER TABLE style_guide_object_profile
             CHANGE bulk_factor bulk_factor DOUBLE PRECISION DEFAULT \'1\' NOT NULL'
        );
    }
}