<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170503075704 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');
        $this->addSql('ALTER TABLE activity_condition DROP FOREIGN KEY FK_9F4ECD7481C06096');
        $this->addSql('DROP INDEX idx_9f4ecd7481c06096 ON activity_condition');
        $this->addSql('CREATE INDEX IDX_5ACEAFF481C06096 ON activity_condition (activity_id)');
        $this->addSql('ALTER TABLE activity_condition ADD CONSTRAINT FK_9F4ECD7481C06096 FOREIGN KEY (activity_id) REFERENCES activities (id) ON DELETE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_31C2774D77153098 ON states (code)');
        $this->addSql('ALTER TABLE users_groups_managers DROP FOREIGN KEY FK_A92EE543A76ED395');
        $this->addSql('ALTER TABLE users_groups_managers DROP FOREIGN KEY FK_A92EE543FE54D947');
        $this->addSql('DROP INDEX unique_user_group ON users_groups_managers');
        $this->addSql('CREATE UNIQUE INDEX unique_user_group_manager ON users_groups_managers (user_id, group_id)');
        $this->addSql('ALTER TABLE users_groups_managers ADD CONSTRAINT FK_A92EE543A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE users_groups_managers ADD CONSTRAINT FK_A92EE543FE54D947 FOREIGN KEY (group_id) REFERENCES groups (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE discount_code_uses DROP FOREIGN KEY FK_ACA504E37F140C37');
        $this->addSql('DROP INDEX idx_aca504e37f140c37 ON discount_code_uses');
        $this->addSql('CREATE INDEX IDX_ACA504E391D29306 ON discount_code_uses (discount_code_id)');
        $this->addSql('ALTER TABLE discount_code_uses ADD CONSTRAINT FK_ACA504E37F140C37 FOREIGN KEY (discount_code_id) REFERENCES discount_codes (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE poll_comment_rates DROP FOREIGN KEY FK_3F8AB5C5A76ED395');
        $this->addSql('ALTER TABLE poll_comment_rates DROP FOREIGN KEY FK_3F8AB5C5F8697D13');
        $this->addSql('DROP INDEX idx_3f8ab5c5f8697d13 ON poll_comment_rates');
        $this->addSql('CREATE INDEX IDX_111987B2F8697D13 ON poll_comment_rates (comment_id)');
        $this->addSql('DROP INDEX idx_3f8ab5c5a76ed395 ON poll_comment_rates');
        $this->addSql('CREATE INDEX IDX_111987B2A76ED395 ON poll_comment_rates (user_id)');
        $this->addSql('ALTER TABLE poll_comment_rates ADD CONSTRAINT FK_3F8AB5C5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE poll_comment_rates ADD CONSTRAINT FK_3F8AB5C5F8697D13 FOREIGN KEY (comment_id) REFERENCES poll_comments (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE karma DROP FOREIGN KEY FK_D708F639A76ED395');
        $this->addSql('DROP INDEX idx_d708f639a76ed395 ON karma');
        $this->addSql('CREATE INDEX IDX_16C9D93DA76ED395 ON karma (user_id)');
        $this->addSql('ALTER TABLE karma ADD CONSTRAINT FK_D708F639A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');
        $this->addSql('ALTER TABLE activity_condition DROP FOREIGN KEY FK_5ACEAFF481C06096');
        $this->addSql('DROP INDEX idx_5aceaff481c06096 ON activity_condition');
        $this->addSql('CREATE INDEX IDX_9F4ECD7481C06096 ON activity_condition (activity_id)');
        $this->addSql('ALTER TABLE activity_condition ADD CONSTRAINT FK_5ACEAFF481C06096 FOREIGN KEY (activity_id) REFERENCES activities (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE discount_code_uses DROP FOREIGN KEY FK_ACA504E391D29306');
        $this->addSql('DROP INDEX idx_aca504e391d29306 ON discount_code_uses');
        $this->addSql('CREATE INDEX IDX_ACA504E37F140C37 ON discount_code_uses (discount_code_id)');
        $this->addSql('ALTER TABLE discount_code_uses ADD CONSTRAINT FK_ACA504E391D29306 FOREIGN KEY (discount_code_id) REFERENCES discount_codes (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE karma DROP FOREIGN KEY FK_16C9D93DA76ED395');
        $this->addSql('DROP INDEX idx_16c9d93da76ed395 ON karma');
        $this->addSql('CREATE INDEX IDX_D708F639A76ED395 ON karma (user_id)');
        $this->addSql('ALTER TABLE karma ADD CONSTRAINT FK_16C9D93DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE poll_comment_rates DROP FOREIGN KEY FK_111987B2F8697D13');
        $this->addSql('ALTER TABLE poll_comment_rates DROP FOREIGN KEY FK_111987B2A76ED395');
        $this->addSql('DROP INDEX idx_111987b2f8697d13 ON poll_comment_rates');
        $this->addSql('CREATE INDEX IDX_3F8AB5C5F8697D13 ON poll_comment_rates (comment_id)');
        $this->addSql('DROP INDEX idx_111987b2a76ed395 ON poll_comment_rates');
        $this->addSql('CREATE INDEX IDX_3F8AB5C5A76ED395 ON poll_comment_rates (user_id)');
        $this->addSql('ALTER TABLE poll_comment_rates ADD CONSTRAINT FK_111987B2F8697D13 FOREIGN KEY (comment_id) REFERENCES poll_comments (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE poll_comment_rates ADD CONSTRAINT FK_111987B2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX UNIQ_31C2774D77153098 ON states');
        $this->addSql('ALTER TABLE users_groups_managers DROP FOREIGN KEY FK_A92EE543A76ED395');
        $this->addSql('ALTER TABLE users_groups_managers DROP FOREIGN KEY FK_A92EE543FE54D947');
        $this->addSql('DROP INDEX unique_user_group_manager ON users_groups_managers');
        $this->addSql('CREATE UNIQUE INDEX unique_user_group ON users_groups_managers (user_id, group_id)');
        $this->addSql('ALTER TABLE users_groups_managers ADD CONSTRAINT FK_A92EE543A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE users_groups_managers ADD CONSTRAINT FK_A92EE543FE54D947 FOREIGN KEY (group_id) REFERENCES groups (id) ON DELETE CASCADE');
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');
    }
}
