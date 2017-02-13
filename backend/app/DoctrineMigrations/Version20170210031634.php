<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170210031634 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE announcement_read (announcement_id INT NOT NULL, user_id INT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_B466493B913AEA17 (announcement_id), INDEX IDX_B466493BA76ED395 (user_id), INDEX IDX_B466493B8B8E8428 (created_at), UNIQUE INDEX UNIQ_B466493B913AEA17A76ED395 (announcement_id, user_id), PRIMARY KEY(announcement_id, user_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE announcement_read ADD CONSTRAINT FK_B466493B913AEA17 FOREIGN KEY (announcement_id) REFERENCES announcements (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE announcement_read ADD CONSTRAINT FK_B466493BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE announcement_read');
    }
}
