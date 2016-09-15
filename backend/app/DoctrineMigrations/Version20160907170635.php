<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160907170635 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
            DELETE ac FROM activity_condition ac
            LEFT JOIN activities a ON ac.activity_id = a.id
            LEFT JOIN groups g ON ac.group_id = g.id
            LEFT JOIN districts d ON ac.district_id = d.id
            LEFT JOIN user u ON ac.user_id = u.id
            LEFT JOIN group_sections gs ON ac.group_section_id = gs.id
            WHERE ac.activity_id IS NULL OR a.id IS NULL 
                OR (ac.group_id IS NOT NULL AND g.id IS NULL) 
                OR (ac.district_id IS NOT NULL AND d.id IS NULL) 
                OR (ac.user_id IS NOT NULL AND u.id IS NULL) 
                OR (ac.group_section_id IS NOT NULL AND gs.id IS NULL)
        ');
        $this->addSql('ALTER TABLE activity_condition DROP FOREIGN KEY FK_9F4ECD7481C06096');
        $this->addSql('ALTER TABLE activity_condition CHANGE activity_id activity_id INT NOT NULL');
        $this->addSql('ALTER TABLE activity_condition ADD CONSTRAINT FK_9F4ECD7481C06096 FOREIGN KEY (activity_id) REFERENCES activities (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE activity_condition ADD CONSTRAINT FK_5ACEAFF4FE54D947 FOREIGN KEY (group_id) REFERENCES groups (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE activity_condition ADD CONSTRAINT FK_5ACEAFF4B08FA272 FOREIGN KEY (district_id) REFERENCES districts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE activity_condition ADD CONSTRAINT FK_5ACEAFF4A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE activity_condition ADD CONSTRAINT FK_5ACEAFF4FEE82C8 FOREIGN KEY (group_section_id) REFERENCES group_sections (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_5ACEAFF4FE54D947 ON activity_condition (group_id)');
        $this->addSql('CREATE INDEX IDX_5ACEAFF4B08FA272 ON activity_condition (district_id)');
        $this->addSql('CREATE INDEX IDX_5ACEAFF4A76ED395 ON activity_condition (user_id)');
        $this->addSql('CREATE INDEX IDX_5ACEAFF4FEE82C8 ON activity_condition (group_section_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE activity_condition DROP FOREIGN KEY FK_5ACEAFF4FE54D947');
        $this->addSql('ALTER TABLE activity_condition DROP FOREIGN KEY FK_5ACEAFF4B08FA272');
        $this->addSql('ALTER TABLE activity_condition DROP FOREIGN KEY FK_5ACEAFF4A76ED395');
        $this->addSql('ALTER TABLE activity_condition DROP FOREIGN KEY FK_5ACEAFF4FEE82C8');
        $this->addSql('DROP INDEX IDX_5ACEAFF4FE54D947 ON activity_condition');
        $this->addSql('DROP INDEX IDX_5ACEAFF4B08FA272 ON activity_condition');
        $this->addSql('DROP INDEX IDX_5ACEAFF4A76ED395 ON activity_condition');
        $this->addSql('DROP INDEX IDX_5ACEAFF4FEE82C8 ON activity_condition');
        $this->addSql('ALTER TABLE activity_condition CHANGE activity_id activity_id INT DEFAULT NULL');
    }
}
