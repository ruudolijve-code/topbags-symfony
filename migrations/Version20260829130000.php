<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260829130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Voegt initiële affiniteiten toe voor Klassiek & Verzorgd.';
    }

    public function up(Schema $schema): void
    {
        $this->seedBrand('ABRO', 38, 'Dit merk past sterk bij Klassiek & Verzorgd');
        $this->seedBrand('Berba', 34, 'Dit merk past sterk bij Klassiek & Verzorgd');
        $this->seedBrand('Tommy Hilfiger', 32, 'Dit merk past bij Klassiek & Verzorgd');

        $this->addSql("INSERT INTO style_guide_affinity (style_world_id, material_id, score, reason, is_active, position) SELECT w.id, m.id, 24, 'Leer past bij een klassieke, verzorgde uitstraling', 1, 100 FROM style_guide_world w JOIN material m ON LOWER(m.name) LIKE '%leer%' WHERE w.slug = 'klassiek-verzorgd'");

        foreach ([['zwart', 16], ['bruin', 12], ['beige', 10], ['blauw', 10]] as [$family, $score]) {
            $this->addSql(sprintf("INSERT INTO style_guide_affinity (style_world_id, color_family, score, reason, is_active, position) SELECT w.id, '%s', %d, 'Deze kleurfamilie past bij Klassiek & Verzorgd', 1, 200 FROM style_guide_world w WHERE w.slug = 'klassiek-verzorgd'", $family, $score));
        }

        foreach ([['cognac', 18], ['taupe', 16], ['donkerbruin', 16]] as [$color, $score]) {
            $this->addSql(sprintf("INSERT INTO style_guide_affinity (style_world_id, color_id, score, reason, is_active, position) SELECT w.id, c.id, %d, 'Deze kleur past sterk bij Klassiek & Verzorgd', 1, 150 FROM style_guide_world w JOIN color c ON c.slug = '%s' WHERE w.slug = 'klassiek-verzorgd'", $score, $color));
        }
    }

    private function seedBrand(string $brand, int $score, string $reason): void
    {
        $this->addSql(sprintf("INSERT INTO style_guide_affinity (style_world_id, brand_id, score, reason, is_active, position) SELECT w.id, b.id, %d, '%s', 1, 10 FROM style_guide_world w JOIN brand b ON LOWER(b.name) = LOWER('%s') WHERE w.slug = 'klassiek-verzorgd'", $score, $reason, $brand));
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE a FROM style_guide_affinity a INNER JOIN style_guide_world w ON w.id = a.style_world_id WHERE w.slug = 'klassiek-verzorgd' AND (a.reason LIKE '%Klassiek & Verzorgd%' OR a.reason = 'Leer past bij een klassieke, verzorgde uitstraling')");
    }
}
