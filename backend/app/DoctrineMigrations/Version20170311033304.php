<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170311033304 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE user_report (user_id INT NOT NULL, followers INT DEFAULT 0 NOT NULL, representatives LONGTEXT NOT NULL COMMENT \'(DC2Type:json_array)\', PRIMARY KEY(user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC');
        $this->addSql("
            INSERT INTO user_report(user_id, followers, representatives)
            SELECT 
                u.id, 
                (SELECT COUNT(*) FROM users_follow uf WHERE u.id = uf.user_id),
                COALESCE(
                    (
                        SELECT CONCAT(
                            '[', 
                            GROUP_CONCAT(
                                CONCAT(
                                    '\"', cr.officialTitle, 
                                    ' ', cr.firstName, 
                                    ' ', cr.lastName, '\"'
                                )
                            ), 
                            ']'
                        ) FROM cicero_representatives cr
                        LEFT JOIN users_districts ud ON cr.district_id = ud.district_id
                        WHERE ud.user_id = u.id
                    ),
                    '[]'
                )
            FROM user u
        ");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE user_report');
    }
}
