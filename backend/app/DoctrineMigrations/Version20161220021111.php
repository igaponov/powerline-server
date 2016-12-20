<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161220021111 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE user_representative_report (user_id INT NOT NULL, president VARCHAR(255) NOT NULL, vice_president VARCHAR(255) NOT NULL, senator1 VARCHAR(255) NOT NULL, senator2 VARCHAR(255) NOT NULL, congressman VARCHAR(255) NOT NULL, PRIMARY KEY(user_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_representative_report ADD CONSTRAINT FK_AB9E735DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql("
            INSERT INTO user_representative_report(user_id, president, vice_president, senator1, senator2, congressman)
            SELECT
              u.id,
              CONCAT_WS(' ', r_p.firstName, r_p.lastName) president,
              CONCAT_WS(' ', r_vp.firstName, r_vp.lastName) vice_president,
              CONCAT_WS(' ', r_s1.firstName, r_s1.lastName) senator1,
              CONCAT_WS(' ', r_s2.firstName, r_s2.lastName) senator2,
              CONCAT_WS(' ', r_c.firstName, r_c.lastName) congressman
            FROM user u
              LEFT JOIN (
                  SELECT ud.district_id AS id, ud.user_id FROM users_districts ud
                  LEFT JOIN districts d ON ud.district_id = d.id
                  WHERE d.district_type = 8
              ) AS d_e ON d_e.user_id = u.id
              LEFT JOIN cicero_representatives r_p ON d_e.id = r_p.district_id AND r_p.officialTitle = 'President'
              LEFT JOIN cicero_representatives r_vp ON d_e.id = r_vp.district_id AND r_vp.officialTitle = 'Vice President'
              LEFT JOIN (
                  SELECT ud.district_id AS id, ud.user_id FROM users_districts ud
                  LEFT JOIN districts d ON ud.district_id = d.id
                  WHERE d.district_type = 7
              ) AS d_u ON d_u.user_id = u.id
              LEFT JOIN cicero_representatives r_s1 ON d_u.id = r_s1.district_id
              LEFT JOIN cicero_representatives r_s2 ON d_u.id = r_s2.district_id AND r_s2.officialTitle = 'Senator' AND r_s1.id != r_s2.id
              LEFT JOIN (
                SELECT ud.district_id AS id, ud.user_id FROM users_districts ud
                LEFT JOIN districts d ON ud.district_id = d.id
                WHERE d.district_type = 6
              ) AS d_l ON d_l.user_id = u.id
              LEFT JOIN cicero_representatives r_c ON d_l.id = r_c.district_id AND r_c.officialTitle = 'Congressman'
            WHERE r_p.id OR r_vp.id OR r_s1.id OR r_s2.id OR r_c.id
            GROUP BY u.id
            ");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE user_representative_report');
    }
}
