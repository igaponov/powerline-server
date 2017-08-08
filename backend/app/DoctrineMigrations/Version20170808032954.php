<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170808032954 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');
        $this->addSql('ALTER TABLE groups ADD CONSTRAINT FK_F06D3970BF396750 FOREIGN KEY (id) REFERENCES group_advanced_attributes (id)');
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F06D3970BF396750 ON groups (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');
        $this->addSql('ALTER TABLE groups DROP FOREIGN KEY FK_F06D3970BF396750');
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');
        $this->addSql('DROP INDEX UNIQ_F06D3970BF396750 ON groups');
    }
}
