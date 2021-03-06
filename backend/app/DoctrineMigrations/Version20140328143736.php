<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20140328143736 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("INSERT INTO comments (petition_id, user_id, type, comment_body, created_at, rate_sum, privacy) SELECT id, user_id, 'micropetition', petition, created_at, 0, 0 FROM micropetitions WHERE type<>'long petition'");
        $this->addSql("INSERT INTO comments (petition_id, user_id, type, comment_body, created_at, rate_sum, privacy) SELECT id, user_id, 'micropetition', title, created_at, 0, 0 FROM micropetitions WHERE type='long petition'");
    }

    public function down(Schema $schema)
    {
        // this down() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("UPDATE comments SET pid = NULL WHERE type='micropetition'");
        $this->addSql("DELETE FROM comments WHERE type='micropetition'");
    }
}
