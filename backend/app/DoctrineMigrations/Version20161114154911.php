<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161114154911 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE announcement_sections (announcement_id INT NOT NULL, group_section_id INT NOT NULL, INDEX IDX_50DBC96B913AEA17 (announcement_id), INDEX IDX_50DBC96BFEE82C8 (group_section_id), PRIMARY KEY(announcement_id, group_section_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE announcement_sections ADD CONSTRAINT FK_50DBC96B913AEA17 FOREIGN KEY (announcement_id) REFERENCES announcements (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE announcement_sections ADD CONSTRAINT FK_50DBC96BFEE82C8 FOREIGN KEY (group_section_id) REFERENCES group_sections (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE announcement_sections');
    }
}
