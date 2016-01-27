<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160127112905 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql("ALTER TABLE user ADD email_hash VARCHAR(40) NOT NULL DEFAULT ''");
        $this->addSql("
        UPDATE user SET email_hash =
            SHA1(
                LOWER(
                    CONCAT(
                        REPLACE(
                            IF (
                              LOCATE('+', email),
                              SUBSTR(email, 1, LOCATE('+', email) - 1),
                              SUBSTR(email, 1, LOCATE('@', email) - 1)
                            ),
                            '.',
                            ''
                        ),
                        SUBSTR(email, LOCATE('@', email))
                    )
                )
            )
        ");
        $this->addSql('ALTER TABLE user ALTER email_hash DROP DEFAULT');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('ALTER TABLE user DROP email_hash');
    }
}
