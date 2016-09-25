<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160913114605 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE users_groups_managers DROP FOREIGN KEY FK_A92EE543A76ED395');
        $this->addSql('ALTER TABLE users_groups_managers DROP FOREIGN KEY FK_A92EE543FE54D947');
        $this->addSql('
            DELETE ug FROM users_groups_managers ug
            LEFT JOIN user u ON ug.user_id = u.id
            LEFT JOIN groups g ON ug.group_id = g.id
            WHERE ug.user_id IS NULL OR u.id IS NULL OR ug.group_id IS NULL OR g.id IS NULL
        ');
        $this->addSql('ALTER TABLE users_groups_managers CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE users_groups_managers ADD CONSTRAINT FK_A92EE543A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE users_groups_managers ADD CONSTRAINT FK_A92EE543FE54D947 FOREIGN KEY (group_id) REFERENCES groups (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE users_groups_managers CHANGE user_id user_id INT DEFAULT NULL');
    }
}