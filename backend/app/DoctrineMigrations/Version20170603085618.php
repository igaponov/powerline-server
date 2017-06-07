<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170603085618 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX ios_token ON user');
        $this->addSql('DROP INDEX android_token ON user');
        $this->addSql('ALTER TABLE user ADD followedDoNotDisturbTill DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, DROP ios_token, DROP android_token');
        $this->addSql('ALTER TABLE users_follow ADD notifying TINYINT(1) DEFAULT \'1\' NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user ADD ios_token VARCHAR(64) DEFAULT NULL COLLATE utf8mb4_unicode_ci, ADD android_token VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, DROP followedDoNotDisturbTill');
        $this->addSql('CREATE INDEX ios_token ON user (ios_token)');
        $this->addSql('CREATE INDEX android_token ON user (android_token)');
        $this->addSql('ALTER TABLE users_follow DROP notifying');
    }
}
