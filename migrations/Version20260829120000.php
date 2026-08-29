<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260829120000 extends AbstractMigration
{
    public function getDescription(): string { return 'Voegt onderhoudbare Stijlgids-affiniteiten, productuitzonderingen en de doelgroepvraag toe.'; }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE style_guide_affinity (id INT AUTO_INCREMENT NOT NULL, style_world_id INT NOT NULL, brand_id INT DEFAULT NULL, material_id INT DEFAULT NULL, color_id INT DEFAULT NULL, category_id INT DEFAULT NULL, color_family VARCHAR(100) DEFAULT NULL, score INT NOT NULL, reason VARCHAR(255) DEFAULT NULL, is_active TINYINT(1) DEFAULT 1 NOT NULL, position SMALLINT DEFAULT 0 NOT NULL, INDEX IDX_SGA_WORLD (style_world_id), INDEX IDX_SGA_BRAND (brand_id), INDEX IDX_SGA_MATERIAL (material_id), INDEX IDX_SGA_COLOR (color_id), INDEX IDX_SGA_CATEGORY (category_id), PRIMARY KEY(id), CONSTRAINT CHK_SGA_ONE_TARGET CHECK ((brand_id IS NOT NULL) + (material_id IS NOT NULL) + (color_id IS NOT NULL) + (category_id IS NOT NULL) + (color_family IS NOT NULL) = 1), CONSTRAINT FK_SGA_WORLD FOREIGN KEY (style_world_id) REFERENCES style_guide_world (id) ON DELETE CASCADE, CONSTRAINT FK_SGA_BRAND FOREIGN KEY (brand_id) REFERENCES brand (id) ON DELETE CASCADE, CONSTRAINT FK_SGA_MATERIAL FOREIGN KEY (material_id) REFERENCES material (id) ON DELETE CASCADE, CONSTRAINT FK_SGA_COLOR FOREIGN KEY (color_id) REFERENCES color (id) ON DELETE CASCADE, CONSTRAINT FK_SGA_CATEGORY FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE style_guide_product_override (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, style_world_id INT NOT NULL, score_adjustment INT NOT NULL, reason VARCHAR(255) DEFAULT NULL, is_active TINYINT(1) DEFAULT 1 NOT NULL, INDEX IDX_SGPO_PRODUCT (product_id), INDEX IDX_SGPO_WORLD (style_world_id), UNIQUE INDEX uniq_style_guide_product_world_override (product_id, style_world_id), PRIMARY KEY(id), CONSTRAINT FK_SGPO_PRODUCT FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE, CONSTRAINT FK_SGPO_WORLD FOREIGN KEY (style_world_id) REFERENCES style_guide_world (id) ON DELETE CASCADE) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql("INSERT INTO style_guide_question (code, title, subtitle, help_text, description, selection_type, position, is_active) SELECT 'target_audience', 'Voor wie zoek je een tas?', NULL, NULL, 'Kies een doelgroep, of laat alle tassen meenemen.', 'single', 5, 1 WHERE NOT EXISTS (SELECT 1 FROM style_guide_question WHERE code = 'target_audience')");
        $this->addSql("INSERT INTO style_guide_answer (question_id, code, label, description, position, is_active) SELECT q.id, data.code, data.label, NULL, data.position, 1 FROM style_guide_question q JOIN (SELECT 'dames' code, 'Dames' label, 10 position UNION ALL SELECT 'heren', 'Heren', 20 UNION ALL SELECT 'geen-voorkeur', 'Geen voorkeur', 30) data WHERE q.code = 'target_audience' AND NOT EXISTS (SELECT 1 FROM style_guide_answer a WHERE a.question_id = q.id AND a.code = data.code)");

        $this->seedBrand('luxe-elegant', 'Abro', 40, 'Dit merk past sterk bij Luxe & Elegant');
        $this->seedBrand('luxe-elegant', 'Berba', 35, 'Dit merk past sterk bij Luxe & Elegant');
        $this->seedBrand('luxe-elegant', 'Smaak Amsterdam', 38, 'Dit merk past sterk bij Luxe & Elegant');
        $this->seedBrand('casual-chic', 'Tommy Hilfiger', 32, 'Dit merk past bij Casual Chic');
        $this->seedBrand('bohemian-kleurrijk', 'Spikes & Sparrow', 38, 'Dit merk past sterk bij Bohemian & Kleurrijk');
        $this->seedBrand('bohemian-kleurrijk', 'Zebra Trends', 34, 'Dit merk past sterk bij Bohemian & Kleurrijk');
        $this->addSql("INSERT INTO style_guide_affinity (style_world_id, material_id, score, reason, is_active, position) SELECT w.id, m.id, 25, 'Leer versterkt deze elegante stijl', 1, 100 FROM style_guide_world w JOIN material m ON LOWER(m.name) LIKE '%leer%' WHERE w.slug = 'luxe-elegant'");
        foreach ([['luxe-elegant','zwart',16],['luxe-elegant','bruin',12],['luxe-elegant','beige',10],['bohemian-kleurrijk','rood',16],['bohemian-kleurrijk','geel',16],['bohemian-kleurrijk','groen',16],['bohemian-kleurrijk','bruin',12],['casual-chic','blauw',16],['casual-chic','rood',10],['casual-chic','wit',14],['casual-chic','beige',12]] as [$world, $family, $score]) {
            $this->seedFamily($world, $family, $score);
        }
        foreach ([['luxe-elegant','cognac',18],['luxe-elegant','taupe',18],['luxe-elegant','donkerbruin',18],['bohemian-kleurrijk','cognac',18],['bohemian-kleurrijk','camel',18],['casual-chic','burgundy',18],['casual-chic','taupe',16]] as [$world, $color, $score]) {
            $this->seedColor($world, $color, $score);
        }
    }

    private function seedBrand(string $world, string $brand, int $score, string $reason): void
    {
        $this->addSql(sprintf("INSERT INTO style_guide_affinity (style_world_id, brand_id, score, reason, is_active, position) SELECT w.id, b.id, %d, '%s', 1, 10 FROM style_guide_world w JOIN brand b ON LOWER(b.name) = LOWER('%s') WHERE w.slug = '%s'", $score, $reason, $brand, $world));
    }
    private function seedFamily(string $world, string $family, int $score): void
    {
        $this->addSql(sprintf("INSERT INTO style_guide_affinity (style_world_id, color_family, score, reason, is_active, position) SELECT w.id, '%s', %d, 'Deze kleurfamilie past bij jouw stijlwereld', 1, 200 FROM style_guide_world w WHERE w.slug = '%s'", $family, $score, $world));
    }
    private function seedColor(string $world, string $color, int $score): void
    {
        $this->addSql(sprintf("INSERT INTO style_guide_affinity (style_world_id, color_id, score, reason, is_active, position) SELECT w.id, c.id, %d, 'Deze kleur past sterk bij jouw stijlwereld', 1, 150 FROM style_guide_world w JOIN color c ON c.slug = '%s' WHERE w.slug = '%s'", $score, $color, $world));
    }
    public function down(Schema $schema): void
    {
        $this->addSql("DELETE a FROM style_guide_answer a INNER JOIN style_guide_question q ON q.id = a.question_id WHERE q.code = 'target_audience'");
        $this->addSql("DELETE FROM style_guide_question WHERE code = 'target_audience'");
        $this->addSql('DROP TABLE style_guide_product_override');
        $this->addSql('DROP TABLE style_guide_affinity');
    }
}
