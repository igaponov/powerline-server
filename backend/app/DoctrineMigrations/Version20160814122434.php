<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160814122434 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');

        $this->addSql('ALTER TABLE comments DROP FOREIGN KEY FK_5F9E962A5550C4ED');
        $this->addSql('ALTER TABLE poll_questions_comments_rate DROP FOREIGN KEY FK_3F8AB5C5F8697D13');
        $this->addSql('ALTER TABLE activities DROP FOREIGN KEY FK_B5F1AFE5AEC7D346');
        $this->addSql('ALTER TABLE comments DROP FOREIGN KEY FK_15824CD8AEC7D346');
        $this->addSql('ALTER TABLE hash_tags_petitions DROP FOREIGN KEY FK_20931056AEC7D346');
        $this->addSql('ALTER TABLE micropetitions_answers DROP FOREIGN KEY FK_9E77053DAEC7D346');
        $this->addSql('ALTER TABLE petition_subscribers DROP FOREIGN KEY FK_5E6065B0AEC7D346');

        $this->addSql('CREATE TABLE user_posts (id INT AUTO_INCREMENT NOT NULL, group_id INT NOT NULL, user_id INT NOT NULL, body LONGTEXT NOT NULL, html_body LONGTEXT NOT NULL, created_at DATETIME NOT NULL, expired_at DATETIME NOT NULL, user_expire_interval INT NOT NULL, boosted TINYINT(1) DEFAULT \'0\' NOT NULL, cached_hash_tags LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', metadata LONGTEXT NOT NULL COMMENT \'(DC2Type:object)\', INDEX IDX_6A9F41A2FE54D947 (group_id), INDEX IDX_6A9F41A2A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        // fill table user_posts
        $this->addSql('INSERT INTO user_posts(group_id, user_id, body, html_body, created_at, expired_at, user_expire_interval, boosted, cached_hash_tags, metadata) SELECT group_id, user_id, petition, petition_body_html, created_at, expire_at, user_expire_interval, publish_status, cached_hash_tags, metadata FROM micropetitions WHERE type = ?', ['quorum']);
        $this->addSql('ALTER TABLE activities ADD post_id INT DEFAULT NULL');
        // update table activities with posts
        $this->addSql('UPDATE activities a LEFT JOIN micropetitions m ON a.petition_id = m.id SET a.post_id=a.petition_id, a.petition_id = NULL, a.type = "post" WHERE m.type = ?', ['quorum']);
        $this->addSql('UPDATE activities a LEFT JOIN micropetitions m ON a.petition_id = m.id SET a.type = "user-petition" WHERE m.type != ?', ['quorum']);

        $this->addSql('CREATE TABLE hash_tags_posts (post_id INT NOT NULL, hashtag_id INT NOT NULL, INDEX IDX_8923E3564B89032C (post_id), INDEX IDX_8923E356FB34EF56 (hashtag_id), PRIMARY KEY(post_id, hashtag_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        // fill table hash_tags_posts
        $this->addSql('INSERT INTO hash_tags_posts(post_id, hashtag_id) SELECT htp.petition_id, htp.hash_tag_id FROM hash_tags_petitions htp LEFT JOIN micropetitions m ON htp.petition_id = m.id WHERE m.type = ?', ['quorum']);
        // clean table hash_tags_petitions
        $this->addSql('DELETE htp FROM hash_tags_petitions htp LEFT JOIN micropetitions m ON htp.petition_id = m.id WHERE m.type = ?', ['quorum']);

        $this->addSql('CREATE TABLE user_petitions (id INT AUTO_INCREMENT NOT NULL, group_id INT NOT NULL, user_id INT NOT NULL, title VARCHAR(255) NOT NULL, body LONGTEXT NOT NULL, html_body LONGTEXT NOT NULL, outsiders_sign TINYINT(1) DEFAULT \'0\' NOT NULL, created_at DATETIME NOT NULL, boosted TINYINT(1) DEFAULT \'0\' NOT NULL, organization_needed TINYINT(1) DEFAULT \'0\' NOT NULL, cached_hash_tags LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', metadata LONGTEXT NOT NULL COMMENT \'(DC2Type:object)\', INDEX IDX_82184ADEFE54D947 (group_id), INDEX IDX_82184ADEA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        // fill table user_petitions
        $this->addSql('INSERT INTO user_petitions(group_id, user_id, title, body, html_body, outsiders_sign, created_at, boosted, organization_needed, cached_hash_tags, metadata) SELECT group_id, user_id, title, petition, petition_body_html, is_outsiders_sign, created_at, publish_status, CASE WHEN type = ? THEN true ELSE false END, cached_hash_tags, metadata FROM micropetitions WHERE type IN (?, ?)', ['open letter', 'open letter', 'long petition']);

        $this->addSql('CREATE TABLE user_petition_comments (id INT AUTO_INCREMENT NOT NULL, pid INT DEFAULT NULL, petition_id INT NOT NULL, user_id INT DEFAULT NULL, comment_body LONGTEXT NOT NULL, comment_body_html LONGTEXT NOT NULL, created_at DATETIME NOT NULL, rate_sum INT NOT NULL, rates_count INT DEFAULT NULL, privacy INT NOT NULL, INDEX IDX_EC14CA805550C4ED (pid), INDEX IDX_EC14CA80AEC7D346 (petition_id), INDEX IDX_EC14CA80A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        // fill table user_petition_comments
        $this->addSql('INSERT INTO user_petition_comments(pid, petition_id, user_id, comment_body, comment_body_html, created_at, rate_sum, rates_count, privacy) SELECT c.pid, c.petition_id, c.user_id, c.comment_body, c.comment_body_html, c.created_at, c.rate_sum, c.rates_count, c.privacy FROM comments c LEFT JOIN micropetitions m ON c.petition_id = m.id WHERE m.type IN (?, ?)', ['open letter', 'long petition']);

        $this->addSql('CREATE TABLE user_petition_signatures (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, petition_id INT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_2C4ECC3AA76ED395 (user_id), INDEX IDX_2C4ECC3AAEC7D346 (petition_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        // fill table user_petition_signatures
        $this->addSql('INSERT INTO user_petition_signatures(petition_id, user_id, created_at) SELECT ma.petition_id, ma.user_id, ma.created_at FROM micropetitions_answers ma LEFT JOIN micropetitions m ON ma.petition_id = m.id WHERE m.type IN (?, ?)', ['open letter', 'long petition']);

        $this->addSql('CREATE TABLE poll_comments (id INT AUTO_INCREMENT NOT NULL, pid INT DEFAULT NULL, question_id INT NOT NULL, user_id INT DEFAULT NULL, comment_body LONGTEXT NOT NULL, comment_body_html LONGTEXT NOT NULL, created_at DATETIME NOT NULL, rate_sum INT NOT NULL, rates_count INT DEFAULT NULL, privacy INT NOT NULL, INDEX IDX_E904EE9A5550C4ED (pid), INDEX IDX_E904EE9A1E27F6BF (question_id), INDEX IDX_E904EE9AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        // fill table poll_comments
        $this->addSql('INSERT INTO poll_comments(pid, question_id, user_id, comment_body, comment_body_html, created_at, rate_sum, rates_count, privacy) SELECT c.pid, c.question_id, c.user_id, c.comment_body, c.comment_body_html, c.created_at, c.rate_sum, c.rates_count, c.privacy FROM comments c WHERE c.question_id IS NOT NULL');

        $this->addSql('CREATE TABLE post_comments (id INT AUTO_INCREMENT NOT NULL, pid INT DEFAULT NULL, post_id INT NOT NULL, user_id INT DEFAULT NULL, comment_body LONGTEXT NOT NULL, comment_body_html LONGTEXT NOT NULL, created_at DATETIME NOT NULL, rate_sum INT NOT NULL, rates_count INT DEFAULT NULL, privacy INT NOT NULL, INDEX IDX_E0731F8B5550C4ED (pid), INDEX IDX_E0731F8B4B89032C (post_id), INDEX IDX_E0731F8BA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        // fill table post_comments
        $this->addSql('INSERT INTO post_comments(pid, post_id, user_id, comment_body, comment_body_html, created_at, rate_sum, rates_count, privacy) SELECT c.pid, c.petition_id, c.user_id, c.comment_body, c.comment_body_html, c.created_at, c.rate_sum, c.rates_count, c.privacy FROM comments c LEFT JOIN micropetitions m ON c.petition_id = m.id WHERE m.type = ?', ['quorum']);

        $this->addSql('CREATE TABLE post_votes (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, post_id INT NOT NULL, `option` INT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_C690F620A76ED395 (user_id), INDEX IDX_C690F6204B89032C (post_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        // fill table post_votes
        $this->addSql('INSERT INTO post_votes(post_id, user_id, `option`, created_at) SELECT ma.petition_id, ma.user_id, ma.option_id, ma.created_at FROM micropetitions_answers ma LEFT JOIN micropetitions m ON ma.petition_id = m.id WHERE m.type = ?', ['quorum']);

        $this->addSql('CREATE TABLE post_subscribers (user_id INT NOT NULL, post_id INT NOT NULL, INDEX IDX_C38D9C83A76ED395 (user_id), INDEX IDX_C38D9C834B89032C (post_id), PRIMARY KEY(user_id, post_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        // fill table petition_subscribers
        $this->addSql('INSERT INTO post_subscribers(user_id, post_id) SELECT ps.user_id, ps.petition_id FROM petition_subscribers ps LEFT JOIN micropetitions m ON ps.petition_id = m.id WHERE m.type = ?', ['quorum']);
        //clean table petition_subscribers
        $this->addSql('DELETE ps FROM petition_subscribers ps LEFT JOIN micropetitions m ON ps.petition_id = m.id WHERE m.type = ?', ['quorum']);

        $this->addSql('ALTER TABLE user_posts ADD CONSTRAINT FK_6A9F41A2FE54D947 FOREIGN KEY (group_id) REFERENCES groups (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_posts ADD CONSTRAINT FK_6A9F41A2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('DELETE htp FROM hash_tags_posts htp LEFT JOIN user_posts up ON htp.post_id = up.id LEFT JOIN hash_tags ht ON ht.id = htp.hashtag_id WHERE up.id IS NULL');
        $this->addSql('ALTER TABLE hash_tags_posts ADD CONSTRAINT FK_8923E3564B89032C FOREIGN KEY (post_id) REFERENCES user_posts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE hash_tags_posts ADD CONSTRAINT FK_8923E356FB34EF56 FOREIGN KEY (hashtag_id) REFERENCES hash_tags (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_petitions ADD CONSTRAINT FK_82184ADEFE54D947 FOREIGN KEY (group_id) REFERENCES groups (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_petitions ADD CONSTRAINT FK_82184ADEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('DELETE upc FROM user_petition_comments upc LEFT JOIN user_petition_comments upc2 ON upc.pid = upc2.id WHERE upc2.id IS NULL');
        $this->addSql('ALTER TABLE user_petition_comments ADD CONSTRAINT FK_EC14CA805550C4ED FOREIGN KEY (pid) REFERENCES user_petition_comments (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_petition_comments ADD CONSTRAINT FK_EC14CA80AEC7D346 FOREIGN KEY (petition_id) REFERENCES user_petitions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_petition_comments ADD CONSTRAINT FK_EC14CA80A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_petition_signatures ADD CONSTRAINT FK_2C4ECC3AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('DELETE ups FROM user_petition_signatures ups LEFT JOIN user_petitions up ON ups.petition_id = up.id WHERE up.id IS NULL');
        $this->addSql('ALTER TABLE user_petition_signatures ADD CONSTRAINT FK_2C4ECC3AAEC7D346 FOREIGN KEY (petition_id) REFERENCES user_petitions (id) ON DELETE CASCADE');
        $this->addSql('DELETE pc FROM poll_comments pc LEFT JOIN poll_comments pc2 ON pc.pid = pc2.id WHERE pc2.id IS NULL');
        $this->addSql('ALTER TABLE poll_comments ADD CONSTRAINT FK_E904EE9A5550C4ED FOREIGN KEY (pid) REFERENCES poll_comments (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE poll_comments ADD CONSTRAINT FK_E904EE9A1E27F6BF FOREIGN KEY (question_id) REFERENCES poll_questions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE poll_comments ADD CONSTRAINT FK_E904EE9AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('DELETE pc FROM post_comments pc LEFT JOIN post_comments pc2 ON pc.pid = pc2.id WHERE pc2.id IS NULL');
        $this->addSql('ALTER TABLE post_comments ADD CONSTRAINT FK_E0731F8B5550C4ED FOREIGN KEY (pid) REFERENCES post_comments (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post_comments ADD CONSTRAINT FK_E0731F8B4B89032C FOREIGN KEY (post_id) REFERENCES user_posts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post_comments ADD CONSTRAINT FK_E0731F8BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post_votes ADD CONSTRAINT FK_C690F620A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('DELETE pv FROM post_votes pv LEFT JOIN user_posts up ON pv.post_id = up.id WHERE up.id IS NULL');
        $this->addSql('ALTER TABLE post_votes ADD CONSTRAINT FK_C690F6204B89032C FOREIGN KEY (post_id) REFERENCES user_posts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post_subscribers ADD CONSTRAINT FK_C38D9C83A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post_subscribers ADD CONSTRAINT FK_C38D9C834B89032C FOREIGN KEY (post_id) REFERENCES user_posts (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE comments');
        $this->dropForeignKeySafe('activities', 'FK_B5F1AFE5AEC7D346');
        $this->addSql('DROP TABLE micropetitions');
        $this->addSql('DROP TABLE micropetitions_answers');
        $this->addSql('ALTER TABLE activities ADD CONSTRAINT FK_B5F1AFE54B89032C FOREIGN KEY (post_id) REFERENCES user_posts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE activities ADD CONSTRAINT FK_B5F1AFE5AEC7D346 FOREIGN KEY (petition_id) REFERENCES user_petitions (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_B5F1AFE54B89032C ON activities (post_id)');
        $this->dropForeignKeySafe('hash_tags_petitions', 'FK_20931056AEC7D346');
        $this->addSql('ALTER TABLE hash_tags_petitions DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE hash_tags_petitions ADD CONSTRAINT FK_20931056AEC7D346 FOREIGN KEY (petition_id) REFERENCES user_petitions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE hash_tags_petitions ADD PRIMARY KEY (petition_id, hash_tag_id)');
        $this->addSql('ALTER TABLE hash_tags_questions DROP FOREIGN KEY FK_28B783B9AB18B62D');
        $this->addSql('DROP INDEX IDX_28B783B9AB18B62D ON hash_tags_questions');
        $this->addSql('ALTER TABLE hash_tags_questions DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE hash_tags_questions CHANGE hash_tag_id hashtag_id INT NOT NULL');
        $this->addSql('ALTER TABLE hash_tags_questions ADD CONSTRAINT FK_28B783B9FB34EF56 FOREIGN KEY (hashtag_id) REFERENCES hash_tags (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_28B783B9FB34EF56 ON hash_tags_questions (hashtag_id)');
        $this->addSql('ALTER TABLE hash_tags_questions ADD PRIMARY KEY (question_id, hashtag_id)');
        $this->dropForeignKeySafe('poll_questions_comments_rate', 'FK_3F8AB5C5F8697D13');
        $this->addSql('DELETE pc FROM poll_questions_comments_rate pc LEFT JOIN user_petition_comments pc2 ON pc.comment_id = pc2.id WHERE pc2.id IS NULL');
        $this->addSql('ALTER TABLE poll_questions_comments_rate ADD CONSTRAINT FK_3F8AB5C5F8697D13 FOREIGN KEY (comment_id) REFERENCES user_petition_comments (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX IDX_5E6065B0AEC7D346 ON petition_subscribers');
        $this->addSql('ALTER TABLE petition_subscribers DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE petition_subscribers CHANGE petition_id userpetition_id INT NOT NULL');
        $this->addSql('ALTER TABLE petition_subscribers ADD CONSTRAINT FK_5E6065B06AEBE5D2 FOREIGN KEY (userpetition_id) REFERENCES user_petitions (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_5E6065B06AEBE5D2 ON petition_subscribers (userpetition_id)');
        $this->addSql('ALTER TABLE petition_subscribers ADD PRIMARY KEY (user_id, userpetition_id)');

        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE activities DROP FOREIGN KEY FK_B5F1AFE54B89032C');
        $this->addSql('ALTER TABLE hash_tags_posts DROP FOREIGN KEY FK_8923E3564B89032C');
        $this->addSql('ALTER TABLE post_comments DROP FOREIGN KEY FK_E0731F8B4B89032C');
        $this->addSql('ALTER TABLE post_votes DROP FOREIGN KEY FK_C690F6204B89032C');
        $this->addSql('ALTER TABLE post_subscribers DROP FOREIGN KEY FK_C38D9C834B89032C');
        $this->addSql('ALTER TABLE activities DROP FOREIGN KEY FK_B5F1AFE5AEC7D346');
        $this->addSql('ALTER TABLE hash_tags_petitions DROP FOREIGN KEY FK_20931056AEC7D346');
        $this->addSql('ALTER TABLE user_petition_comments DROP FOREIGN KEY FK_EC14CA80AEC7D346');
        $this->addSql('ALTER TABLE user_petition_signatures DROP FOREIGN KEY FK_2C4ECC3AAEC7D346');
        $this->addSql('ALTER TABLE petition_subscribers DROP FOREIGN KEY FK_5E6065B06AEBE5D2');
        $this->addSql('ALTER TABLE user_petition_comments DROP FOREIGN KEY FK_EC14CA805550C4ED');
        $this->addSql('ALTER TABLE poll_questions_comments_rate DROP FOREIGN KEY FK_3F8AB5C5F8697D13');
        $this->addSql('ALTER TABLE poll_comments DROP FOREIGN KEY FK_E904EE9A5550C4ED');
        $this->addSql('ALTER TABLE post_comments DROP FOREIGN KEY FK_E0731F8B5550C4ED');
        $this->addSql('CREATE TABLE comments (id INT AUTO_INCREMENT NOT NULL, question_id INT DEFAULT NULL, user_id INT DEFAULT NULL, petition_id INT DEFAULT NULL, pid INT DEFAULT NULL, comment_body LONGTEXT NOT NULL, created_at DATETIME NOT NULL, rate_sum INT NOT NULL, privacy INT NOT NULL, type VARCHAR(255) NOT NULL, rates_count INT DEFAULT NULL, comment_body_html LONGTEXT NOT NULL, INDEX IDX_15824CD85550C4ED (pid), INDEX IDX_15824CD8A76ED395 (user_id), INDEX IDX_15824CD81E27F6BF (question_id), INDEX IDX_15824CD8AEC7D346 (petition_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE micropetitions (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, group_id INT NOT NULL, title VARCHAR(255) NOT NULL, petition LONGTEXT NOT NULL, link VARCHAR(255) DEFAULT NULL, is_outsiders_sign TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, expire_at DATETIME NOT NULL, user_expire_interval INT NOT NULL, publish_status INT NOT NULL, type VARCHAR(255) NOT NULL, cached_hash_tags LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', metadata LONGTEXT NOT NULL COMMENT \'(DC2Type:object)\', petition_body_html LONGTEXT NOT NULL, INDEX IDX_DE658FA0FE54D947 (group_id), INDEX IDX_DE658FA0A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE micropetitions_answers (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, petition_id INT DEFAULT NULL, option_id INT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_9E77053DA76ED395 (user_id), INDEX IDX_9E77053DAEC7D346 (petition_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE comments ADD CONSTRAINT FK_15824CD81E27F6BF FOREIGN KEY (question_id) REFERENCES poll_questions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE comments ADD CONSTRAINT FK_15824CD8A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE comments ADD CONSTRAINT FK_15824CD8AEC7D346 FOREIGN KEY (petition_id) REFERENCES micropetitions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE comments ADD CONSTRAINT FK_5F9E962A5550C4ED FOREIGN KEY (pid) REFERENCES comments (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE micropetitions ADD CONSTRAINT FK_DE658FA0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE micropetitions ADD CONSTRAINT FK_DE658FA0FE54D947 FOREIGN KEY (group_id) REFERENCES groups (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE micropetitions_answers ADD CONSTRAINT FK_9E77053DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE micropetitions_answers ADD CONSTRAINT FK_9E77053DAEC7D346 FOREIGN KEY (petition_id) REFERENCES micropetitions (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE user_posts');
        $this->addSql('DROP TABLE hash_tags_posts');
        $this->addSql('DROP TABLE user_petitions');
        $this->addSql('DROP TABLE user_petition_comments');
        $this->addSql('DROP TABLE user_petition_signatures');
        $this->addSql('DROP TABLE poll_comments');
        $this->addSql('DROP TABLE post_comments');
        $this->addSql('DROP TABLE post_votes');
        $this->addSql('DROP TABLE post_subscribers');
        $this->dropForeignKeySafe('activities', 'FK_B5F1AFE5AEC7D346');
        $this->addSql('DROP INDEX IDX_B5F1AFE54B89032C ON activities');
        $this->addSql('ALTER TABLE activities DROP post_id');
        $this->addSql('ALTER TABLE activities ADD CONSTRAINT FK_B5F1AFE5AEC7D346 FOREIGN KEY (petition_id) REFERENCES micropetitions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE groups DROP INDEX UNIQ_F06D397049928441, ADD INDEX group_officialName_ind (official_name)');
        $this->dropForeignKeySafe('hash_tags_petitions', 'FK_20931056AEC7D346');
        $this->addSql('ALTER TABLE hash_tags_petitions DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE hash_tags_petitions ADD CONSTRAINT FK_20931056AEC7D346 FOREIGN KEY (petition_id) REFERENCES micropetitions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE hash_tags_petitions ADD PRIMARY KEY (hash_tag_id, petition_id)');
        $this->dropForeignKeySafe('hash_tags_questions', 'FK_28B783B9FB34EF56');
        $this->addSql('DROP INDEX IDX_28B783B9FB34EF56 ON hash_tags_questions');
        $this->addSql('ALTER TABLE hash_tags_questions DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE hash_tags_questions CHANGE hashtag_id hash_tag_id INT NOT NULL');
        $this->addSql('ALTER TABLE hash_tags_questions ADD CONSTRAINT FK_28B783B9AB18B62D FOREIGN KEY (hash_tag_id) REFERENCES hash_tags (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_28B783B9AB18B62D ON hash_tags_questions (hash_tag_id)');
        $this->addSql('ALTER TABLE hash_tags_questions ADD PRIMARY KEY (hash_tag_id, question_id)');
        $this->addSql('DROP INDEX IDX_5E6065B06AEBE5D2 ON petition_subscribers');
        $this->addSql('ALTER TABLE petition_subscribers DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE petition_subscribers CHANGE userpetition_id petition_id INT NOT NULL');
        $this->addSql('ALTER TABLE petition_subscribers ADD CONSTRAINT FK_5E6065B0AEC7D346 FOREIGN KEY (petition_id) REFERENCES micropetitions (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_5E6065B0AEC7D346 ON petition_subscribers (petition_id)');
        $this->addSql('ALTER TABLE petition_subscribers ADD PRIMARY KEY (user_id, petition_id)');
        $this->dropForeignKeySafe('poll_questions_comments_rate', 'FK_3F8AB5C5F8697D13');
        $this->addSql('ALTER TABLE poll_questions_comments_rate ADD CONSTRAINT FK_3F8AB5C5F8697D13 FOREIGN KEY (comment_id) REFERENCES comments (id) ON DELETE CASCADE');
    }

    private function dropForeignKeySafe($tableName, $keyName)
    {
        $this->addSql("set @var=if((SELECT true FROM information_schema.TABLE_CONSTRAINTS WHERE
            CONSTRAINT_SCHEMA = DATABASE() AND
            TABLE_NAME        = ? AND
            CONSTRAINT_NAME   = ? AND
            CONSTRAINT_TYPE   = 'FOREIGN KEY') = true, 'ALTER TABLE $tableName
            drop foreign key $keyName','select 1');

            prepare stmt from @var;
            execute stmt;
            deallocate prepare stmt;", [$tableName, $keyName]);
    }
}
