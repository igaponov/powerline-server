<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160908020142 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE notification_endpoints DROP FOREIGN KEY FK_7AF462EFA76ED395');
        $this->addSql('
            DELETE ne FROM notification_endpoints ne 
            LEFT JOIN user u ON ne.user_id = u.id
            WHERE ne.user_id IS NULL OR u.id IS NULL
        ');
        $this->addSql('ALTER TABLE notification_endpoints CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE notification_endpoints ADD CONSTRAINT FK_7AF462EFA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE notification_endpoints CHANGE user_id user_id INT DEFAULT NULL');
    }
}
