<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160204065335 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('ALTER TABLE users_groups ADD permissions_city TINYINT(1) DEFAULT \'0\' NOT NULL, ADD permissions_state TINYINT(1) DEFAULT \'0\' NOT NULL, ADD permissions_country TINYINT(1) DEFAULT \'0\' NOT NULL, ADD permissions_zip_code TINYINT(1) DEFAULT \'0\' NOT NULL');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('ALTER TABLE users_groups DROP permissions_city, DROP permissions_state, DROP permissions_country, DROP permissions_zip_code');
    }
}
