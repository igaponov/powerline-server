<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160918152415 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
            DELETE b FROM bookmarks b
            LEFT JOIN activities a ON b.item_id = a.id
            WHERE b.item_id IS NULL OR a.id IS NULL
        ');
        $this->addSql('ALTER TABLE bookmarks ADD CONSTRAINT FK_78D2C140126F525E FOREIGN KEY (item_id) REFERENCES activities (id) ON DELETE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_78D2C140126F525E ON bookmarks (item_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE bookmarks DROP FOREIGN KEY FK_78D2C140126F525E');
        $this->addSql('DROP INDEX UNIQ_78D2C140126F525E ON bookmarks');
    }
}
