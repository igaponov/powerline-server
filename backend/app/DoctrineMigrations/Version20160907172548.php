<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160907172548 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE bookmarks DROP FOREIGN KEY FK_78D2C140A76ED395');
        $this->addSql('DELETE b FROM bookmarks b LEFT JOIN user u ON b.user_id = u.id WHERE b.user_id IS NULL OR u.id IS NULL');
        $this->addSql('ALTER TABLE bookmarks CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE bookmarks ADD CONSTRAINT FK_78D2C140A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE bookmarks CHANGE user_id user_id INT DEFAULT NULL');
    }
}
