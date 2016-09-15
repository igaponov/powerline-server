<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160913103604 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE poll_options DROP FOREIGN KEY FK_2C6077B81E27F6BF');
        $this->addSql('
            DELETE o FROM poll_options o
            LEFT JOIN poll_questions q ON o.question_id = q.id
            WHERE o.question_id IS NULL OR q.id IS NULL
        ');
        $this->addSql('ALTER TABLE poll_options CHANGE question_id question_id INT NOT NULL');
        $this->addSql('ALTER TABLE poll_options ADD CONSTRAINT FK_2C6077B81E27F6BF FOREIGN KEY (question_id) REFERENCES poll_questions (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE poll_options CHANGE question_id question_id INT DEFAULT NULL');
    }
}
