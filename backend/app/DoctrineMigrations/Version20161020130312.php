<?php

namespace Application\Migrations;

use Cocur\Slugify\Slugify;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161020130312 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE groups ADD slug VARCHAR(255) DEFAULT NULL');
        $stmt = $this->connection->query('SELECT id, IF (official_name, official_name, username) AS name FROM groups');
        $stmt->execute();
        $slugify = new Slugify();
        $groups = [];
        while ($row = $stmt->fetch()) {
            $name = $slug = $slugify->slugify($row['name']);
            $i = 1;
            while (in_array($slug, $groups)) {
                $slug = $name.'-'.$i;
                $i++;
            }
            $groups[] = $slug;
            $this->addSql('UPDATE groups SET slug = ? WHERE id = ?', [$slug, $row['id']]);
        }
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F06D3970989D9B62 ON groups (slug)');
        $this->addSql('ALTER TABLE groups CHANGE slug slug VARCHAR(255) NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX UNIQ_F06D3970989D9B62 ON groups');
        $this->addSql('ALTER TABLE groups DROP slug');
    }
}
