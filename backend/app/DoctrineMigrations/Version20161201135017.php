<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161201135017 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $rows = $this->connection->fetchAll('
            SELECT post_id, COUNT(id) count FROM activities 
            WHERE post_id IS NOT NULL 
            GROUP BY post_id 
            HAVING COUNT(id) > 1
        ');
        foreach ($rows as $row) {
            $this->addSql("DELETE FROM activities WHERE post_id = ? LIMIT {$row['count']}", [$row['post_id']]);
        }
        $rows = $this->connection->fetchAll('
            SELECT petition_id, COUNT(id) count FROM activities 
            WHERE petition_id IS NOT NULL 
            GROUP BY petition_id 
            HAVING COUNT(id) > 1
        ');
        foreach ($rows as $row) {
            $this->addSql("DELETE FROM activities WHERE petition_id = ? LIMIT {$row['count']}", [$row['petition_id']]);
        }
        $rows = $this->connection->fetchAll('
            SELECT question_id, COUNT(id) count FROM activities 
            WHERE question_id IS NOT NULL 
            GROUP BY question_id 
            HAVING COUNT(id) > 1
        ');
        foreach ($rows as $row) {
            $this->addSql("DELETE FROM activities WHERE question_id = ? LIMIT {$row['count']}", [$row['question_id']]);
        }
        $this->addSql('ALTER TABLE activities DROP INDEX IDX_B5F1AFE5AEC7D346, ADD UNIQUE INDEX UNIQ_B5F1AFE5AEC7D346 (petition_id)');
        $this->addSql('ALTER TABLE activities DROP INDEX IDX_B5F1AFE51E27F6BF, ADD UNIQUE INDEX UNIQ_B5F1AFE51E27F6BF (question_id)');
        $this->addSql('ALTER TABLE activities DROP INDEX IDX_B5F1AFE54B89032C, ADD UNIQUE INDEX UNIQ_B5F1AFE54B89032C (post_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE activities DROP INDEX UNIQ_B5F1AFE51E27F6BF, ADD INDEX IDX_B5F1AFE51E27F6BF (question_id)');
        $this->addSql('ALTER TABLE activities DROP INDEX UNIQ_B5F1AFE5AEC7D346, ADD INDEX IDX_B5F1AFE5AEC7D346 (petition_id)');
        $this->addSql('ALTER TABLE activities DROP INDEX UNIQ_B5F1AFE54B89032C, ADD INDEX IDX_B5F1AFE54B89032C (post_id)');
    }
}
