<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170123071656 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_representative_report CHANGE president president VARCHAR(255) DEFAULT NULL, CHANGE vice_president vice_president VARCHAR(255) DEFAULT NULL, CHANGE senator1 senator1 VARCHAR(255) DEFAULT NULL, CHANGE senator2 senator2 VARCHAR(255) DEFAULT NULL, CHANGE congressman congressman VARCHAR(255) DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_representative_report CHANGE president president VARCHAR(255) NOT NULL, CHANGE vice_president vice_president VARCHAR(255) NOT NULL, CHANGE senator1 senator1 VARCHAR(255) NOT NULL, CHANGE senator2 senator2 VARCHAR(255) NOT NULL, CHANGE congressman congressman VARCHAR(255) NOT NULL');
    }
}
