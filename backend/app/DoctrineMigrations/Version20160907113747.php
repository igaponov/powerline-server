<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160907113747 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE groups_fields_values DROP FOREIGN KEY FK_7984E7FAA76ED395');
        $this->addSql('
            DELETE gfv FROM groups_fields_values gfv 
            LEFT JOIN groups_fields gf ON gfv.field_id = gf.id
            LEFT JOIN user u ON gfv.user_id = u.id
            WHERE gfv.field_id IS NULL OR gfv.user_id IS NULL OR gf.id IS NULL OR u.id IS NULL
        ');
        $this->addSql('ALTER TABLE groups_fields_values CHANGE field_id field_id INT NOT NULL, CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE groups_fields_values ADD CONSTRAINT FK_7984E7FAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE groups_fields_values DROP FOREIGN KEY FK_7984E7FAA76ED395');
        $this->addSql('ALTER TABLE groups_fields_values CHANGE field_id field_id INT DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE groups_fields_values ADD CONSTRAINT FK_7984E7FAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }
}
