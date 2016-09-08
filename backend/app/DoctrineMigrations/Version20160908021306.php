<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160908021306 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE poll_answers DROP FOREIGN KEY FK_AC854B391E27F6BF');
        $this->addSql('ALTER TABLE poll_answers DROP FOREIGN KEY FK_AC854B39A76ED395');
        $this->addSql('
            DELETE pa FROM poll_answers pa 
            LEFT JOIN poll_questions pq ON pa.question_id = pq.id
            LEFT JOIN poll_options po ON pa.option_id = po.id
            LEFT JOIN user u ON pa.user_id = u.id
            WHERE pa.question_id IS NOT NULL OR pq.id IS NOT NULL
                OR pa.option_id IS NOT NULL OR po.id IS NOT NULL
                OR pa.user_id IS NOT NULL OR u.id IS NOT NULL
        ');
        $this->addSql('ALTER TABLE poll_answers CHANGE question_id question_id INT NOT NULL, CHANGE user_id user_id INT NOT NULL, CHANGE privacy privacy SMALLINT NOT NULL');
        $this->addSql('ALTER TABLE poll_answers ADD CONSTRAINT FK_AC854B391E27F6BF FOREIGN KEY (question_id) REFERENCES poll_questions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE poll_answers ADD CONSTRAINT FK_AC854B39A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE poll_answers DROP FOREIGN KEY FK_AC854B391E27F6BF');
        $this->addSql('ALTER TABLE poll_answers CHANGE user_id user_id INT DEFAULT NULL, CHANGE question_id question_id INT DEFAULT NULL, CHANGE privacy privacy INT NOT NULL');
        $this->addSql('ALTER TABLE poll_answers ADD CONSTRAINT FK_AC854B391E27F6BF FOREIGN KEY (question_id) REFERENCES poll_questions (id)');
    }
}
