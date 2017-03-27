<?php

namespace Application\Migrations;

use Civix\CoreBundle\Entity\Group;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170324042552 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_report ADD country VARCHAR(255) DEFAULT \'\' NOT NULL, ADD state VARCHAR(255) DEFAULT \'\' NOT NULL, ADD locality VARCHAR(255) DEFAULT \'\' NOT NULL, ADD districts LONGTEXT NOT NULL COMMENT \'(DC2Type:json_array)\'');
        $this->addSql(
            "UPDATE user_report ur 
            SET 
                ur.country = (
                    SELECT COALESCE(g.official_name, '') FROM groups g 
                    LEFT JOIN users_groups ug ON g.id = ug.group_id
                    WHERE ug.user_id = ur.user_id AND g.group_type = :country
                ),
                ur.state = (
                    SELECT COALESCE(g.official_name, '') FROM groups g 
                    LEFT JOIN users_groups ug ON g.id = ug.group_id
                    WHERE ug.user_id = ur.user_id AND g.group_type = :state
                ),
                ur.locality = (
                    SELECT COALESCE(g.official_name, '') FROM groups g 
                    LEFT JOIN users_groups ug ON g.id = ug.group_id
                    WHERE ug.user_id = ur.user_id AND g.group_type = :local
                ),
                ur.districts = (
                    SELECT CONCAT('[', GROUP_CONCAT(CONCAT('\"', d.label, '\"')), ']')
                    FROM districts d
                    LEFT JOIN users_districts ud ON d.id = ud.district_id
                    WHERE ud.user_id = ur.user_id AND d.label != ''
                )
            ",
            [
                ':country' => Group::GROUP_TYPE_COUNTRY,
                ':state' => Group::GROUP_TYPE_STATE,
                ':local' => Group::GROUP_TYPE_LOCAL,
            ]
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_report DROP country, DROP state, DROP locality, DROP districts');
    }
}
