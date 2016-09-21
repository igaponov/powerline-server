<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160921170912 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE user SET email =
            LOWER(
                CONCAT(
                    REPLACE(
                        IF (
                          LOCATE(\'+\', email),
                          SUBSTR(email, 1, LOCATE(\'+\', email) - 1),
                          SUBSTR(email, 1, LOCATE(\'@\', email) - 1)
                        ),
                        \'.\',
                        \'\'
                    ),
                    SUBSTR(email, LOCATE(\'@\', email))
                )
            )');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
    }
}
