<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260812124802 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE style_guide_answer (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(100) NOT NULL, label VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, position SMALLINT DEFAULT 0 NOT NULL, is_active TINYINT DEFAULT 1 NOT NULL, question_id INT NOT NULL, INDEX IDX_A95065381E27F6BF (question_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE style_guide_answer_world_score (id INT AUTO_INCREMENT NOT NULL, score INT DEFAULT 0 NOT NULL, answer_id INT NOT NULL, style_world_id INT NOT NULL, INDEX IDX_2FF19FDFAA334807 (answer_id), INDEX IDX_2FF19FDFA8F7E937 (style_world_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE style_guide_question (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(100) NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, selection_type VARCHAR(20) NOT NULL, position SMALLINT DEFAULT 0 NOT NULL, is_active TINYINT DEFAULT 1 NOT NULL, UNIQUE INDEX UNIQ_E24A6B8677153098 (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE style_guide_world (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, emotion VARCHAR(100) DEFAULT NULL, motto VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, result_text LONGTEXT DEFAULT NULL, position SMALLINT DEFAULT 0 NOT NULL, is_active TINYINT DEFAULT 1 NOT NULL, UNIQUE INDEX UNIQ_6B4E8FC4989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE style_guide_answer ADD CONSTRAINT FK_A95065381E27F6BF FOREIGN KEY (question_id) REFERENCES style_guide_question (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE style_guide_answer_world_score ADD CONSTRAINT FK_2FF19FDFAA334807 FOREIGN KEY (answer_id) REFERENCES style_guide_answer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE style_guide_answer_world_score ADD CONSTRAINT FK_2FF19FDFA8F7E937 FOREIGN KEY (style_world_id) REFERENCES style_guide_world (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE style_guide_answer DROP FOREIGN KEY FK_A95065381E27F6BF');
        $this->addSql('ALTER TABLE style_guide_answer_world_score DROP FOREIGN KEY FK_2FF19FDFAA334807');
        $this->addSql('ALTER TABLE style_guide_answer_world_score DROP FOREIGN KEY FK_2FF19FDFA8F7E937');
        $this->addSql('DROP TABLE style_guide_answer');
        $this->addSql('DROP TABLE style_guide_answer_world_score');
        $this->addSql('DROP TABLE style_guide_question');
        $this->addSql('DROP TABLE style_guide_world');
    }
}
