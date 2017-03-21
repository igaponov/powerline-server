<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170312124456 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE poll_response_report (user_id INT NOT NULL, poll_id INT NOT NULL, group_id INT NOT NULL, text LONGTEXT NOT NULL, answer VARCHAR(255) NOT NULL, comment LONGTEXT NOT NULL, PRIMARY KEY(user_id, poll_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC');
        $this->addSql("
            INSERT INTO poll_response_report(user_id, poll_id, group_id, `text`, answer, `comment`)
            SELECT 
                pa.user_id,
                pq.id,
                pq.group_id,
                COALESCE(pq.title, pq.subject, ''),
                COALESCE(po.value, ''),
                COALESCE(pa.comment, '')
            FROM poll_answers pa
            LEFT JOIN poll_questions pq ON pa.question_id = pq.id
            LEFT JOIN poll_options po ON pa.option_id = po.id
        ");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE poll_response_report');
    }
}
