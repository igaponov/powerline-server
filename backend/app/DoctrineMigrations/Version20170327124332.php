<?php

namespace Application\Migrations;

use Civix\CoreBundle\Entity\Post\Vote;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170327124332 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE post_response_reports (user_id INT NOT NULL, post_id INT NOT NULL, vote VARCHAR(255) NOT NULL, PRIMARY KEY(user_id, post_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC');
        $this->addSql('CREATE TABLE petition_response_reports (user_id INT NOT NULL, petition_id INT NOT NULL, PRIMARY KEY(user_id, petition_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC');
        $this->addSql("
            INSERT INTO post_response_reports(user_id, post_id, vote)
            SELECT 
                pv.user_id, 
                pv.post_id, 
                CASE 
                    WHEN pv.`option` = :up THEN 'upvote' 
                    WHEN pv.`option` = :down THEN 'downvote' 
                    ELSE 'ignore' 
                END
            FROM post_votes pv
        ", [
            ':up' => Vote::OPTION_UPVOTE,
            ':down' => Vote::OPTION_DOWNVOTE,
        ]);
        $this->addSql("
            INSERT INTO petition_response_reports(user_id, petition_id)
            SELECT ps.user_id, ps.petition_id FROM user_petition_signatures ps
        ");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE post_response_reports');
        $this->addSql('DROP TABLE petition_response_reports');
    }
}
