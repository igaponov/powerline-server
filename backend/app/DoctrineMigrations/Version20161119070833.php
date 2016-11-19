<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161119070833 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE questions_recipients');
        $this->addSql('ALTER TABLE stripe_accounts CHANGE bank_accounts bank_accounts LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json_array)\'');
        $this->addSql('ALTER TABLE stripe_customers DROP FOREIGN KEY FK_DDDE68EBFC3FF006');
        $this->addSql('DROP INDEX IDX_DDDE68EBFC3FF006 ON stripe_customers');
        $this->addSql('ALTER TABLE stripe_customers DROP representative_id, CHANGE cards cards LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json_array)\'');
        $this->addSql('ALTER TABLE poll_questions DROP FOREIGN KEY FK_410B7D21E7451FE1');
        $this->addSql('DROP INDEX IDX_410B7D21E7451FE1 ON poll_questions');
        $this->addSql('ALTER TABLE poll_questions DROP report_recipient_id, DROP report_recipient_group');
        $this->addSql('ALTER TABLE representatives DROP FOREIGN KEY FK_FC93E5357226D8C1');
        $this->addSql('DROP INDEX UNIQ_FC93E535F85E0677 ON representatives');
        $this->addSql('DROP INDEX rep_firstName_ind ON representatives');
        $this->addSql('DROP INDEX rep_lastName_ind ON representatives');
        $this->addSql('DELETE FROM representatives');
        $this->addSql('ALTER TABLE representatives ADD user_id INT NOT NULL, ADD privatePhone VARCHAR(255) NOT NULL, ADD privateEmail VARCHAR(255) NOT NULL, DROP firstname, DROP lastname, DROP username, DROP password, DROP salt, DROP token, DROP avatar_src, CHANGE email email VARCHAR(255) NOT NULL, CHANGE is_nonlegislative is_nonlegislative TINYINT(1) DEFAULT NULL, CHANGE officialphone phone VARCHAR(15) NOT NULL');
        $this->addSql('ALTER TABLE representatives ADD CONSTRAINT FK_FC93E535A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE representatives ADD CONSTRAINT FK_FC93E5357226D8C1 FOREIGN KEY (local_group) REFERENCES groups (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_FC93E535A76ED395 ON representatives (user_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FC93E535A76ED3957226D8C1 ON representatives (user_id, local_group)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE questions_recipients (question_id INT NOT NULL, representative_id INT NOT NULL, INDEX IDX_89128B481E27F6BF (question_id), INDEX IDX_89128B48FC3FF006 (representative_id), PRIMARY KEY(question_id, representative_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE questions_recipients ADD CONSTRAINT FK_89128B481E27F6BF FOREIGN KEY (question_id) REFERENCES poll_questions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE questions_recipients ADD CONSTRAINT FK_89128B48FC3FF006 FOREIGN KEY (representative_id) REFERENCES representatives (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE poll_questions ADD report_recipient_id INT DEFAULT NULL, ADD report_recipient_group VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE poll_questions ADD CONSTRAINT FK_410B7D21E7451FE1 FOREIGN KEY (report_recipient_id) REFERENCES representatives (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_410B7D21E7451FE1 ON poll_questions (report_recipient_id)');
        $this->addSql('ALTER TABLE representatives DROP FOREIGN KEY FK_FC93E535A76ED395');
        $this->addSql('ALTER TABLE representatives DROP FOREIGN KEY FK_FC93E5357226D8C1');
        $this->addSql('DROP INDEX IDX_FC93E535A76ED395 ON representatives');
        $this->addSql('DROP INDEX UNIQ_FC93E535A76ED3957226D8C1 ON representatives');
        $this->addSql('ALTER TABLE representatives ADD firstname VARCHAR(255) NOT NULL, ADD lastname VARCHAR(255) NOT NULL, ADD username VARCHAR(255) DEFAULT NULL, ADD password VARCHAR(255) DEFAULT NULL, ADD salt VARCHAR(255) DEFAULT NULL, ADD token VARCHAR(255) DEFAULT NULL, ADD avatar_src VARCHAR(255) DEFAULT NULL, DROP user_id, DROP privatePhone, DROP privateEmail, CHANGE email email VARCHAR(50) NOT NULL, CHANGE is_nonlegislative is_nonlegislative INT DEFAULT NULL, CHANGE phone officialPhone VARCHAR(15) NOT NULL');
        $this->addSql('ALTER TABLE representatives ADD CONSTRAINT FK_FC93E5357226D8C1 FOREIGN KEY (local_group) REFERENCES groups (id) ON DELETE SET NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FC93E535F85E0677 ON representatives (username)');
        $this->addSql('CREATE INDEX rep_firstName_ind ON representatives (firstname)');
        $this->addSql('CREATE INDEX rep_lastName_ind ON representatives (lastname)');
        $this->addSql('ALTER TABLE stripe_accounts CHANGE bank_accounts bank_accounts LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE stripe_customers ADD representative_id INT DEFAULT NULL, CHANGE cards cards LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE stripe_customers ADD CONSTRAINT FK_DDDE68EBFC3FF006 FOREIGN KEY (representative_id) REFERENCES representatives (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_DDDE68EBFC3FF006 ON stripe_customers (representative_id)');
    }
}
