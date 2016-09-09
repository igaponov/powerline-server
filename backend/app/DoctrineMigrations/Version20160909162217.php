<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160909162217 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE user_petition_comment_rates (id INT AUTO_INCREMENT NOT NULL, comment_id INT NOT NULL, user_id INT NOT NULL, rate_value SMALLINT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_754867CDF8697D13 (comment_id), INDEX IDX_754867CDA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE post_comment_rates (id INT AUTO_INCREMENT NOT NULL, comment_id INT NOT NULL, user_id INT NOT NULL, rate_value SMALLINT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_F23F8DDEF8697D13 (comment_id), INDEX IDX_F23F8DDEA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_petition_comment_rates ADD CONSTRAINT FK_754867CDF8697D13 FOREIGN KEY (comment_id) REFERENCES user_petition_comments (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_petition_comment_rates ADD CONSTRAINT FK_754867CDA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post_comment_rates ADD CONSTRAINT FK_F23F8DDEF8697D13 FOREIGN KEY (comment_id) REFERENCES post_comments (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post_comment_rates ADD CONSTRAINT FK_F23F8DDEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE poll_questions_comments_rate DROP FOREIGN KEY FK_3F8AB5C5F8697D13');
        $this->addSql('ALTER TABLE poll_questions_comments_rate DROP FOREIGN KEY FK_3F8AB5C5A76ED395');
        $this->addSql('
            DELETE pr FROM poll_questions_comments_rate pr
            LEFT JOIN poll_comments c ON pr.comment_id = c.id
            LEFT JOIN user u ON pr.user_id = u.id
            WHERE pr.comment_id IS NULL OR c.id IS NULL OR pr.user_id IS NULL OR u.id IS NULL
        ');
        $this->addSql('ALTER TABLE poll_questions_comments_rate CHANGE comment_id comment_id INT NOT NULL, CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE poll_questions_comments_rate ADD CONSTRAINT FK_3F8AB5C5F8697D13 FOREIGN KEY (comment_id) REFERENCES poll_comments (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE poll_questions_comments_rate ADD CONSTRAINT FK_3F8AB5C5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE poll_questions_comments_rate RENAME poll_comment_rates');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE user_petition_comment_rates');
        $this->addSql('DROP TABLE post_comment_rates');
        $this->addSql('ALTER TABLE poll_comment_rates RENAME poll_questions_comments_rate');
        $this->addSql('ALTER TABLE poll_questions_comments_rate CHANGE comment_id comment_id INT DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL');
    }
}
