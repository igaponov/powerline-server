<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160923145302 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE poll_questions DROP FOREIGN KEY FK_410B7D21A76ED395');
        $this->addSql('
            UPDATE poll_questions p 
            LEFT JOIN groups g ON p.group_id = g.id
            LEFT JOIN users_groups ug ON g.id = ug.group_id
            SET p.user_id = 
              CASE 
                WHEN g.user_id IS NOT NULL THEN g.user_id 
                WHEN ug.user_id IS NOT NULL THEN ug.user_id 
                ELSE NULL 
              END
            WHERE p.user_id IS NULL
        ');
        $this->addSql('DELETE FROM poll_questions WHERE user_id IS NULL');
        $this->addSql('ALTER TABLE poll_questions CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE poll_questions ADD CONSTRAINT FK_410B7D21A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE poll_questions CHANGE user_id user_id INT DEFAULT NULL');
    }
}
