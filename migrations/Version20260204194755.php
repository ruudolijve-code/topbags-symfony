<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260204194755 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE airline_faq DROP FOREIGN KEY `FK_AIRLINE_FAQ_AIRLINE`');
        $this->addSql('ALTER TABLE airline_faq CHANGE answer answer LONGTEXT NOT NULL, CHANGE position position INT NOT NULL');
        $this->addSql('ALTER TABLE airline_faq ADD CONSTRAINT FK_77ECA502130D0C16 FOREIGN KEY (airline_id) REFERENCES airline (id)');
        $this->addSql('ALTER TABLE airline_faq RENAME INDEX idx_airline_faq_airline TO IDX_77ECA502130D0C16');
        $this->addSql('ALTER TABLE airline_ticket_type DROP FOREIGN KEY `FK_ACFED4A5130D0C16`');
        $this->addSql('ALTER TABLE airline_ticket_type ADD CONSTRAINT FK_ACFED4A5130D0C16 FOREIGN KEY (airline_id) REFERENCES airline (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE category CHANGE is_active is_active TINYINT DEFAULT 1 NOT NULL, CHANGE show_in_menu show_in_menu TINYINT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE category_context CHANGE position position INT NOT NULL');
        $this->addSql('ALTER TABLE transport_category_map CHANGE category_id category_id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE airline_faq DROP FOREIGN KEY FK_77ECA502130D0C16');
        $this->addSql('ALTER TABLE airline_faq CHANGE answer answer TEXT NOT NULL, CHANGE position position INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE airline_faq ADD CONSTRAINT `FK_AIRLINE_FAQ_AIRLINE` FOREIGN KEY (airline_id) REFERENCES airline (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE airline_faq RENAME INDEX idx_77eca502130d0c16 TO IDX_AIRLINE_FAQ_AIRLINE');
        $this->addSql('ALTER TABLE airline_ticket_type DROP FOREIGN KEY FK_ACFED4A5130D0C16');
        $this->addSql('ALTER TABLE airline_ticket_type ADD CONSTRAINT `FK_ACFED4A5130D0C16` FOREIGN KEY (airline_id) REFERENCES airline (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE category CHANGE is_active is_active TINYINT NOT NULL, CHANGE show_in_menu show_in_menu TINYINT NOT NULL');
        $this->addSql('ALTER TABLE category_context CHANGE position position INT DEFAULT 0');
        $this->addSql('ALTER TABLE transport_category_map CHANGE category_id category_id INT DEFAULT NULL');
    }
}
