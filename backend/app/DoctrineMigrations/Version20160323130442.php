<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160323130442 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE users_groups_managers (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, group_id INT DEFAULT NULL, created_at DATETIME NOT NULL, status SMALLINT NOT NULL, INDEX IDX_A92EE543A76ED395 (user_id), INDEX IDX_A92EE543FE54D947 (group_id), UNIQUE INDEX unique_user_group (user_id, group_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE users_groups_managers ADD CONSTRAINT FK_A92EE543A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE users_groups_managers ADD CONSTRAINT FK_A92EE543FE54D947 FOREIGN KEY (group_id) REFERENCES groups (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE groups CHANGE transparency transparency VARCHAR(255) NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE users_groups_managers');
        $this->addSql('ALTER TABLE groups CHANGE transparency transparency VARCHAR(255) DEFAULT \'public\' NOT NULL');
    }
}
