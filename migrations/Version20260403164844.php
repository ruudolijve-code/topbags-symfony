<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260403164844 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE shop_order DROP FOREIGN KEY `FK_323FC9CA9395C3F3`');
        $this->addSql('DROP INDEX IDX_323FC9CA9395C3F3 ON shop_order');
        $this->addSql('ALTER TABLE shop_order CHANGE customer_id customer_user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE shop_order ADD CONSTRAINT FK_323FC9CABBB3772B FOREIGN KEY (customer_user_id) REFERENCES customer_user (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_323FC9CABBB3772B ON shop_order (customer_user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE shop_order DROP FOREIGN KEY FK_323FC9CABBB3772B');
        $this->addSql('DROP INDEX IDX_323FC9CABBB3772B ON shop_order');
        $this->addSql('ALTER TABLE shop_order CHANGE customer_user_id customer_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE shop_order ADD CONSTRAINT `FK_323FC9CA9395C3F3` FOREIGN KEY (customer_id) REFERENCES customer_user (id) ON UPDATE NO ACTION ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_323FC9CA9395C3F3 ON shop_order (customer_id)');
    }
}
