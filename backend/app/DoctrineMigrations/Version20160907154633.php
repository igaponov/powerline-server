<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160907154633 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE poll_questions_comments_rate DROP FOREIGN KEY FK_3F8AB5C5F8697D13');
        $this->addSql('ALTER TABLE poll_questions_comments_rate ADD CONSTRAINT FK_3F8AB5C5F8697D13 FOREIGN KEY (comment_id) REFERENCES poll_comments (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE poll_questions_comments_rate DROP FOREIGN KEY FK_3F8AB5C5F8697D13');
        $this->addSql('ALTER TABLE poll_questions_comments_rate ADD CONSTRAINT FK_3F8AB5C5F8697D13 FOREIGN KEY (comment_id) REFERENCES user_petition_comments (id) ON DELETE CASCADE');
    }
}
