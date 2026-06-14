<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260614222904 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE magazine_article_product (magazine_article_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_FD0FE3B9370C57A3 (magazine_article_id), INDEX IDX_FD0FE3B94584665A (product_id), PRIMARY KEY (magazine_article_id, product_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE magazine_faq (id INT AUTO_INCREMENT NOT NULL, question VARCHAR(255) NOT NULL, answer LONGTEXT NOT NULL, position INT NOT NULL, is_active TINYINT NOT NULL, article_id INT NOT NULL, INDEX IDX_E013C9CC7294869C (article_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE magazine_article_product ADD CONSTRAINT FK_FD0FE3B9370C57A3 FOREIGN KEY (magazine_article_id) REFERENCES magazine_article (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE magazine_article_product ADD CONSTRAINT FK_FD0FE3B94584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE magazine_faq ADD CONSTRAINT FK_E013C9CC7294869C FOREIGN KEY (article_id) REFERENCES magazine_article (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE magazine_article_product DROP FOREIGN KEY FK_FD0FE3B9370C57A3');
        $this->addSql('ALTER TABLE magazine_article_product DROP FOREIGN KEY FK_FD0FE3B94584665A');
        $this->addSql('ALTER TABLE magazine_faq DROP FOREIGN KEY FK_E013C9CC7294869C');
        $this->addSql('DROP TABLE magazine_article_product');
        $this->addSql('DROP TABLE magazine_faq');
    }
}
