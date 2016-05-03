<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160405174012 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('SET FOREIGN_KEY_CHECKS = 1;');
        $this->addSql('ALTER TABLE activities ADD CONSTRAINT FK_B5F1AFE51E27F6BF FOREIGN KEY (question_id) REFERENCES poll_questions (id)');
        $this->addSql('CREATE INDEX IDX_B5F1AFE51E27F6BF ON activities (question_id)');
        $this->addSql('CREATE INDEX sent_at_idx ON activities (sent_at)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE activities DROP FOREIGN KEY FK_B5F1AFE51E27F6BF');
        $this->addSql('DROP INDEX IDX_B5F1AFE51E27F6BF ON activities');
        $this->addSql('DROP INDEX sent_at_idx ON activities');
    }
}
