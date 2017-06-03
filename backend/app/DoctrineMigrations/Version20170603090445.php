<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170603090445 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE activity_condition_users');
        $this->addSql('ALTER TABLE activity_condition DROP is_superuser');
        $this->addSql('DROP INDEX UNIQ_31C2774D77153098 ON states');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE activity_condition_users (activity_condition_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_1576286E81CF51F7 (activity_condition_id), INDEX IDX_1576286EA76ED395 (user_id), PRIMARY KEY(activity_condition_id, user_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE activity_condition_users ADD CONSTRAINT FK_1576286E81CF51F7 FOREIGN KEY (activity_condition_id) REFERENCES activity_condition (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE activity_condition_users ADD CONSTRAINT FK_1576286EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE activity_condition ADD is_superuser TINYINT(1) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_31C2774D77153098 ON states (code)');
    }
}
