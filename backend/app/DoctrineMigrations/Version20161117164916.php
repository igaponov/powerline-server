<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161117164916 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE representatives DROP FOREIGN KEY FK_FC93E5355CC5DB90');
        $this->addSql('DROP TABLE representatives_storage');
        $this->addSql('DROP INDEX UNIQ_FC93E5355CC5DB90 ON representatives');
        $this->addSql('ALTER TABLE representatives ADD avatar_src VARCHAR(255) DEFAULT NULL, ADD address1 VARCHAR(255) DEFAULT NULL, ADD address2 VARCHAR(255) DEFAULT NULL, ADD address3 VARCHAR(255) DEFAULT NULL, ADD party VARCHAR(255) DEFAULT NULL, ADD start_term DATE DEFAULT NULL, ADD end_term DATE DEFAULT NULL, ADD facebook VARCHAR(255) DEFAULT NULL, ADD youtube VARCHAR(255) DEFAULT NULL, ADD twitter VARCHAR(255) DEFAULT NULL, ADD openstate_id VARCHAR(255) DEFAULT NULL, ADD updatedAt DATETIME NOT NULL, DROP officialAddress, CHANGE upd_storage_at birthday DATE DEFAULT NULL, CHANGE storage_id cicero_id INT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FC93E535D7739216 ON representatives (cicero_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE representatives_storage (storage_id INT NOT NULL, state VARCHAR(2) DEFAULT NULL, district_id INT DEFAULT NULL, firstName VARCHAR(255) NOT NULL, lastName VARCHAR(255) NOT NULL, officialTitle VARCHAR(255) NOT NULL, phone VARCHAR(15) DEFAULT NULL, fax VARCHAR(15) DEFAULT NULL, email VARCHAR(80) DEFAULT NULL, website VARCHAR(255) DEFAULT NULL, country VARCHAR(2) DEFAULT NULL, city VARCHAR(255) DEFAULT NULL, address1 VARCHAR(255) DEFAULT NULL, address2 VARCHAR(255) DEFAULT NULL, address3 VARCHAR(255) DEFAULT NULL, avatar_src VARCHAR(255) NOT NULL, avatar_file_name VARCHAR(255) DEFAULT NULL, party VARCHAR(255) DEFAULT NULL, birthday DATE DEFAULT NULL, start_term DATE DEFAULT NULL, end_term DATE DEFAULT NULL, facebook VARCHAR(255) DEFAULT NULL, youtube VARCHAR(255) DEFAULT NULL, twitter VARCHAR(255) DEFAULT NULL, openstate_id VARCHAR(255) DEFAULT NULL, updated_at DATETIME NOT NULL, INDEX IDX_AAB1F751B08FA272 (district_id), INDEX repst_firstName_ind (firstName), INDEX repst_lastName_ind (lastName), INDEX repst_officialTitle_ind (officialTitle), INDEX IDX_AAB1F751A393D2FB (state), PRIMARY KEY(storage_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE representatives_storage ADD CONSTRAINT FK_AAB1F751A393D2FB FOREIGN KEY (state) REFERENCES states (code) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE representatives_storage ADD CONSTRAINT FK_AAB1F751B08FA272 FOREIGN KEY (district_id) REFERENCES districts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE representatives ADD officialAddress LONGTEXT NOT NULL, ADD upd_storage_at DATE DEFAULT NULL, DROP avatar_src, DROP address1, DROP address2, DROP address3, DROP party, DROP birthday, DROP start_term, DROP end_term, DROP facebook, DROP youtube, DROP twitter, DROP openstate_id, DROP updatedAt, CHANGE cicero_id storage_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE representatives ADD CONSTRAINT FK_FC93E5355CC5DB90 FOREIGN KEY (storage_id) REFERENCES representatives_storage (storage_id) ON DELETE SET NULL');
    }
}
