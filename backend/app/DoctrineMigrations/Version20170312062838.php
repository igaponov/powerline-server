<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170312062838 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE membership_report (user_id INT NOT NULL, group_id INT NOT NULL, group_fields LONGTEXT NOT NULL COMMENT \'(DC2Type:json_array)\', PRIMARY KEY(user_id, group_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC');
        $this->addSql("
            INSERT INTO membership_report(user_id, group_id, group_fields) 
            SELECT 
                ug.user_id, 
                ug.group_id, 
                COALESCE(
                    CONCAT(
                        '{',
                        GROUP_CONCAT(
                            CONCAT('\"', gf.field_name, '\":\"', gfv.field_value, '\"')
                        ),
                        '}'
                    ),
                    '{}'
                )
            FROM users_groups ug
            LEFT JOIN groups_fields_values gfv ON gfv.user_id = ug.user_id
            LEFT JOIN groups_fields gf ON gfv.field_id = gf.id
            GROUP BY ug.user_id, ug.group_id
        ");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE membership_report');
    }
}
