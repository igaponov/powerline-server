<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160706181754 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE activities_read DROP INDEX UNIQ_1DFA339A81C06096, ADD INDEX IDX_1DFA339A81C06096 (activity_id)');
        $this->addSql('ALTER TABLE activities_read DROP INDEX user_activity_ind, ADD UNIQUE INDEX UNIQ_1DFA339A81C06096A76ED395 (activity_id, user_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE activities_read DROP INDEX IDX_1DFA339A81C06096, ADD UNIQUE INDEX UNIQ_1DFA339A81C06096 (activity_id)');
        $this->addSql('ALTER TABLE activities_read DROP INDEX UNIQ_1DFA339A81C06096A76ED395, ADD INDEX user_activity_ind (activity_id, user_id)');
    }
}
