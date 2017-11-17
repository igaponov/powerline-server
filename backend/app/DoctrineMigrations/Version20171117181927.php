<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171117181927 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE blocked_users (blocked_user_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_A3C2E4151EBCBB63 (blocked_user_id), INDEX IDX_A3C2E415A76ED395 (user_id), PRIMARY KEY(blocked_user_id, user_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE blocked_users ADD CONSTRAINT FK_A3C2E4151EBCBB63 FOREIGN KEY (blocked_user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE blocked_users ADD CONSTRAINT FK_A3C2E415A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE blocked_users');
    }
}
