<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160303045007 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('ALTER TABLE micropetitions ADD metadata LONGTEXT NOT NULL COMMENT \'(DC2Type:object)\'');
        $this->addSql(
            'UPDATE micropetitions SET metadata = ? WHERE NOT metadata',
            ['O:47:"Civix\CoreBundle\Entity\Micropetitions\Metadata":3:{s:54:" Civix\CoreBundle\Entity\Micropetitions\Metadata title";N;s:60:" Civix\CoreBundle\Entity\Micropetitions\Metadata description";N;s:54:" Civix\CoreBundle\Entity\Micropetitions\Metadata image";N;}']
        );
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('ALTER TABLE micropetitions DROP metadata');
    }
}
