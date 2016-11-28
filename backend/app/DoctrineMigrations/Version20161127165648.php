<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161127165648 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE groups CHANGE petition_per_month petition_per_month INT DEFAULT 30 NOT NULL');
        $this->addSql('ALTER TABLE users_groups_managers DROP FOREIGN KEY FK_A92EE543FE54D947');
        $this->addSql('ALTER TABLE users_groups_managers CHANGE group_id group_id INT NOT NULL');
        $this->addSql('ALTER TABLE users_groups_managers ADD CONSTRAINT `FK_A92EE543FE54D947` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE groups CHANGE petition_per_month petition_per_month INT DEFAULT 5 NOT NULL');
        $this->addSql('ALTER TABLE users_groups_managers CHANGE group_id group_id INT DEFAULT NULL');
    }
}
