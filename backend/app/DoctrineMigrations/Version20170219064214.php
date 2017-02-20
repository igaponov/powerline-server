<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170219064214 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');
        $this->addSql('ALTER TABLE activities ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE activities_read ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE activity_condition ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE activity_condition_users ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE announcement_sections ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE announcements ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE bookmarks ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE cicero_representatives ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE deferred_invites ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE districts ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE group_sections ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE groups ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE groups_fields ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE groups_fields_values ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE groupsection_user ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE hash_tags ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE hash_tags_petitions ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE hash_tags_posts ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE hash_tags_questions ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE invites ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE migration_versions ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE notification_endpoints ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE notification_invites ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE payments_transaction ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE petition_subscribers ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE poll_answers ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE poll_comment_rates ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE poll_comments ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE poll_educational_context ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE poll_options ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE poll_questions ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE poll_sections ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE poll_subscribers ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE post_comment_rates ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE post_comments ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE post_subscribers ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE post_votes ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE posts ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE question_limits ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE representatives ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE sessions ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE settings ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE social_activities ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE states ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE stripe_accounts ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE stripe_charges ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE stripe_customers ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE subscriptions ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE superusers ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE temp_files ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE user ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE user_petition_comment_rates ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE user_petition_comments ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE user_petition_signatures ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE user_petitions ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE user_posts ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE user_representative_report ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE users_districts ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE users_follow ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE users_groups ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE users_groups_managers ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $columns = $this->connection->fetchAll('
            SELECT table_name, column_name, column_type, is_nullable, column_comment FROM information_schema.columns
            WHERE table_schema = DATABASE() AND character_set_name IS NOT NULL AND collation_name IS NOT NULL AND (character_set_name != ? OR collation_name != ?)
            ORDER BY table_name, ordinal_position;
        ', ['utf8mb4', 'utf8mb4_unicode_ci']);
        foreach ($columns as $column) {
            $this->addSql(sprintf(
                'ALTER TABLE %s CHANGE %s %s %s CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci %s %s',
                $column['table_name'],
                $column['column_name'],
                $column['column_name'],
                $column['column_type'],
                $column['is_nullable'] === 'NO' ? 'NOT NULL' : 'DEFAULT NULL',
                $column['column_comment'] ? "COMMENT '{$column['column_comment']}'" : ''
            ));
        }
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');
        $this->addSql('ALTER TABLE activities ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE activities_read ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE activity_condition ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE activity_condition_users ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE announcement_sections ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE announcements ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE bookmarks ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE cicero_representatives ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE deferred_invites ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE districts ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE group_sections ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE groups ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE groups_fields ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE groups_fields_values ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE groupsection_user ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE hash_tags ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE hash_tags_petitions ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE hash_tags_posts ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE hash_tags_questions ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE invites ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE migration_versions ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE notification_endpoints ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE notification_invites ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE payments_transaction ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE petition_subscribers ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE poll_answers ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE poll_comment_rates ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE poll_comments ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE poll_educational_context ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE poll_options ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE poll_questions ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE poll_sections ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE poll_subscribers ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE post_comment_rates ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE post_comments ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE post_subscribers ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE post_votes ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE posts ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE question_limits ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE representatives ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE sessions ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE settings ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE social_activities ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE states ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE stripe_accounts ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE stripe_charges ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE stripe_customers ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE subscriptions ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE superusers ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE temp_files ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE user ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE user_petition_comment_rates ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE user_petition_comments ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE user_petition_signatures ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE user_petitions ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE user_posts ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE user_representative_report ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE users_districts ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE users_follow ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE users_groups ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE users_groups_managers ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $columns = $this->connection->fetchAll('
            SELECT table_name, column_name, column_type, is_nullable, column_comment FROM information_schema.columns
            WHERE table_schema = DATABASE() AND character_set_name IS NOT NULL AND collation_name IS NOT NULL AND (character_set_name != ? OR collation_name != ?)
            ORDER BY table_name, ordinal_position;
        ', ['utf8', 'utf8_unicode_ci']);
        foreach ($columns as $column) {
            $this->addSql(sprintf(
                'ALTER TABLE %s CHANGE %s %s %s CHARACTER SET utf8 COLLATE utf8_unicode_ci %s %s',
                $column['table_name'],
                $column['column_name'],
                $column['column_name'],
                $column['column_type'],
                $column['is_nullable'] === 'NO' ? 'NOT NULL' : 'DEFAULT NULL',
                $column['column_comment'] ? "COMMENT '{$column['column_comment']}'" : ''
            ));
        }
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');
    }
}
