<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160907045828 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DELETE sc FROM stripe_charges sc LEFT JOIN stripe_accounts sa ON sc.to_account = sa.id WHERE sa.id IS NULL');
        $this->addSql('ALTER TABLE stripe_charges ADD CONSTRAINT FK_152861E01E27F6BF FOREIGN KEY (question_id) REFERENCES poll_questions (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_152861E01E27F6BF ON stripe_charges (question_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE stripe_charges DROP FOREIGN KEY FK_152861E01E27F6BF');
        $this->addSql('DROP INDEX IDX_152861E01E27F6BF ON stripe_charges');
    }
}
