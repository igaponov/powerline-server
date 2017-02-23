<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170219144031 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');
        $this->addSql('ALTER TABLE announcement_read ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE spam_posts ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE spam_user_petitions ROW_FORMAT = DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');
        $this->addSql('ALTER TABLE announcement_read ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE spam_posts ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE spam_user_petitions ROW_FORMAT = COMPACT, CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');
    }
}
