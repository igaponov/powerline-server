<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160907115740 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE activities_read DROP FOREIGN KEY FK_1DFA339AA76ED395');
        $this->addSql('
            DELETE ar FROM activities_read ar 
            LEFT JOIN activities a ON ar.activity_id = a.id
            LEFT JOIN user u ON ar.user_id = u.id
            WHERE ar.activity_id IS NULL OR ar.user_id IS NULL OR a.id IS NULL OR u.id IS NULL
        ');
        $this->addSql('ALTER TABLE activities_read CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE activities_read ADD CONSTRAINT FK_1DFA339AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE activities_read DROP FOREIGN KEY FK_1DFA339AA76ED395');
        $this->addSql('ALTER TABLE activities_read CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE activities_read ADD CONSTRAINT FK_1DFA339AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }
}
