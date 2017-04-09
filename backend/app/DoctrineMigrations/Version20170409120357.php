<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170409120357 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
            UPDATE post_comments c
            LEFT JOIN post_comments c2 ON c2.id = c.pid
            SET c.pid = NULL
            WHERE c2.pid IS NULL 
        ');
        $this->addSql('
            UPDATE poll_comments c
            LEFT JOIN poll_comments c2 ON c2.id = c.pid
            SET c.pid = NULL
            WHERE c2.pid IS NULL 
        ');
        $this->addSql('
            UPDATE user_petition_comments c
            LEFT JOIN user_petition_comments c2 ON c2.id = c.pid
            SET c.pid = NULL
            WHERE c2.pid IS NULL 
        ');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
    }
}
