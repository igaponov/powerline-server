<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160903021452 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE groups DROP FOREIGN KEY FK_F06D3970A2557E1F');
        $this->addSql('UPDATE groups g LEFT JOIN districts d ON g.local_district = d.id SET local_district = NULL WHERE d.id IS NULL');
        $this->addSql('ALTER TABLE groups ADD CONSTRAINT FK_F06D3970A2557E1F FOREIGN KEY (local_district) REFERENCES districts (id) ON DELETE SET NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE groups DROP FOREIGN KEY FK_F06D3970A2557E1F');
        $this->addSql('ALTER TABLE groups ADD CONSTRAINT FK_F06D3970A2557E1F FOREIGN KEY (local_district) REFERENCES districts (id)');
    }
}
