<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170426081340 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE groups 
            SET avatar_file_name = avatar_source_file_name 
            WHERE NOT avatar_file_name AND avatar_source_file_name');
        $this->addSql('UPDATE cicero_representatives 
            SET avatar_file_name = avatar_source_file_name 
            WHERE NOT avatar_file_name AND avatar_source_file_name');
        $this->addSql('UPDATE representatives 
            SET avatar_file_name = avatar_source_file_name 
            WHERE NOT avatar_file_name AND avatar_source_file_name');
        $this->addSql('ALTER TABLE groups DROP avatar_source_file_name');
        $this->addSql('ALTER TABLE cicero_representatives DROP avatar_source_file_name');
        $this->addSql('ALTER TABLE representatives DROP avatar_source_file_name');
        $this->addSql('ALTER TABLE user CHANGE avatar avatar_file_name VARCHAR(255) DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE cicero_representatives ADD avatar_source_file_name VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE groups ADD avatar_source_file_name VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE representatives ADD avatar_source_file_name VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE user CHANGE avatar_file_name avatar VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci');
    }
}
