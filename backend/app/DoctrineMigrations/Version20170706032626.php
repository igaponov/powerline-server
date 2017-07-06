<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170706032626 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_petition_comments DROP FOREIGN KEY FK_EC14CA805550C4ED, DROP FOREIGN KEY FK_EC14CA80A76ED395');
        $this->addSql('ALTER TABLE user_petition_comments ADD CONSTRAINT FK_EC14CA805550C4ED FOREIGN KEY (pid) REFERENCES user_petition_comments (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE poll_comments DROP FOREIGN KEY FK_E904EE9A5550C4ED, DROP FOREIGN KEY FK_E904EE9AA76ED395');
        $this->addSql('ALTER TABLE poll_comments ADD CONSTRAINT FK_E904EE9A5550C4ED FOREIGN KEY (pid) REFERENCES poll_comments (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE post_comments DROP FOREIGN KEY FK_E0731F8B5550C4ED, DROP FOREIGN KEY FK_E0731F8BA76ED395');
        $this->addSql('ALTER TABLE post_comments ADD CONSTRAINT FK_E0731F8B5550C4ED FOREIGN KEY (pid) REFERENCES post_comments (id) ON DELETE SET NULL');

        $this->addSql('DELETE FROM user_petition_comments WHERE user_id IS NULL');
        $this->addSql('DELETE FROM poll_comments WHERE user_id IS NULL');
        $this->addSql('DELETE FROM post_comments WHERE user_id IS NULL');

        $this->addSql('ALTER TABLE user_petition_comments CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE poll_comments CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE post_comments CHANGE user_id user_id INT NOT NULL');

        $this->addSql('ALTER TABLE poll_comments DROP FOREIGN KEY FK_E904EE9A5550C4ED');
        $this->addSql('ALTER TABLE poll_comments ADD CONSTRAINT FK_E904EE9A5550C4ED FOREIGN KEY (pid) REFERENCES poll_comments (id) ON DELETE CASCADE, ADD CONSTRAINT FK_E904EE9AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post_comments DROP FOREIGN KEY FK_E0731F8B5550C4ED');
        $this->addSql('ALTER TABLE post_comments ADD CONSTRAINT FK_E0731F8B5550C4ED FOREIGN KEY (pid) REFERENCES post_comments (id) ON DELETE CASCADE, ADD CONSTRAINT FK_E0731F8BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_petition_comments DROP FOREIGN KEY FK_EC14CA805550C4ED');
        $this->addSql('ALTER TABLE user_petition_comments ADD CONSTRAINT FK_EC14CA805550C4ED FOREIGN KEY (pid) REFERENCES user_petition_comments (id) ON DELETE CASCADE, ADD CONSTRAINT FK_EC14CA80A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE poll_comments CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE post_comments CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user_petition_comments CHANGE user_id user_id INT DEFAULT NULL');
    }
}
