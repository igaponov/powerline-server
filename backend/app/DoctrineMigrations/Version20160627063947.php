<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160627063947 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE activities DROP FOREIGN KEY FK_B5F1AFE5AEC7D346');
        $this->addSql('ALTER TABLE activities DROP FOREIGN KEY FK_B5F1AFE593BEB0AF');
        $this->addSql('ALTER TABLE activities DROP FOREIGN KEY FK_B5F1AFE5A76ED395');
        $this->addSql('ALTER TABLE activities CHANGE updated_at updated_at DATETIME NOT NULL');

        $this->addSql('DELETE FROM activities_read WHERE activity_id NOT IN (SELECT id FROM activities) OR activity_id IS NULL');
        $this->addSql('DELETE FROM activities WHERE question_id NOT IN (SELECT id FROM poll_questions)');
        $this->addSql('DELETE FROM activities WHERE petition_id NOT IN (SELECT id FROM micropetitions)');
        $this->addSql('DELETE FROM activities WHERE superuser_id NOT IN (SELECT id FROM superusers)');
        $this->addSql('DELETE FROM activities WHERE user_id NOT IN (SELECT id FROM user)');

        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');
        $this->addSql('ALTER TABLE activities_read ADD CONSTRAINT FK_1DFA339A81C06096 FOREIGN KEY (activity_id) REFERENCES activities (id) ON DELETE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1DFA339A81C06096 ON activities_read (activity_id)');
        $this->addSql('ALTER TABLE activities ADD CONSTRAINT FK_B5F1AFE51E27F6BF FOREIGN KEY (question_id) REFERENCES poll_questions (id) ON DELETE CASCADE ');
        $this->addSql('ALTER TABLE activities ADD CONSTRAINT FK_B5F1AFE5AEC7D346 FOREIGN KEY (petition_id) REFERENCES micropetitions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE activities ADD CONSTRAINT FK_B5F1AFE593BEB0AF FOREIGN KEY (superuser_id) REFERENCES superusers (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE activities ADD CONSTRAINT FK_B5F1AFE5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');

        $this->addSql('DELETE FROM micropetitions WHERE deleted_at IS NOT NULL');
        $this->addSql('ALTER TABLE micropetitions DROP deleted_at');
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

        $this->addSql('ALTER TABLE activities_read DROP FOREIGN KEY FK_1DFA339A81C06096');
        $this->addSql('DROP INDEX UNIQ_1DFA339A81C06096 ON activities_read');
        $this->addSql('ALTER TABLE activities DROP FOREIGN KEY FK_B5F1AFE51E27F6BF');
        $this->addSql('ALTER TABLE activities DROP FOREIGN KEY FK_B5F1AFE593BEB0AF');
        $this->addSql('ALTER TABLE activities DROP FOREIGN KEY FK_B5F1AFE5A76ED395');
        $this->addSql('ALTER TABLE activities DROP FOREIGN KEY FK_B5F1AFE5AEC7D346');
        $this->addSql('DROP INDEX IDX_B5F1AFE51E27F6BF ON activities');
        $this->addSql('DROP INDEX sent_at_idx ON activities');
        $this->addSql('ALTER TABLE activities CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');
        $this->addSql('ALTER TABLE activities ADD CONSTRAINT FK_B5F1AFE593BEB0AF FOREIGN KEY (superuser_id) REFERENCES superusers (id)');
        $this->addSql('ALTER TABLE activities ADD CONSTRAINT FK_B5F1AFE5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE activities ADD CONSTRAINT FK_B5F1AFE5AEC7D346 FOREIGN KEY (petition_id) REFERENCES micropetitions (id)');
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');
        $this->addSql('ALTER TABLE micropetitions ADD deleted_at DATETIME DEFAULT NULL');
    }
}
