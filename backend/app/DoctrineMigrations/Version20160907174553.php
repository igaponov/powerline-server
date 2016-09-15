<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160907174553 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE groups DROP FOREIGN KEY FK_F06D3970727ACA70');
        $this->addSql('ALTER TABLE groups DROP FOREIGN KEY FK_F06D3970A76ED395');
        $this->addSql('ALTER TABLE groups DROP FOREIGN KEY FK_F06D3970BC754EFF');
        $this->addSql('
            DELETE g FROM groups g 
            LEFT JOIN groups g2 ON g.parent_id = g2.id
            LEFT JOIN user u ON g.user_id = u.id
            LEFT JOIN states s ON g.local_state = s.code
            LEFT JOIN districts d ON g.local_district = d.id
            WHERE (g.parent_id IS NOT NULL AND g2.id IS NULL) 
                OR (g.user_id IS NOT NULL AND u.id IS NULL) 
                OR (g.local_state IS NOT NULL AND s.code IS NULL) 
                OR (g.local_district IS NOT NULL AND d.id IS NULL)
        ');
        $this->addSql('ALTER TABLE groups ADD CONSTRAINT FK_F06D3970727ACA70 FOREIGN KEY (parent_id) REFERENCES groups (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE groups ADD CONSTRAINT FK_F06D3970A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE groups ADD CONSTRAINT FK_F06D3970BC754EFF FOREIGN KEY (local_state) REFERENCES states (code) ON DELETE SET NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE groups DROP FOREIGN KEY FK_F06D3970A76ED395');
        $this->addSql('ALTER TABLE groups DROP FOREIGN KEY FK_F06D3970BC754EFF');
        $this->addSql('ALTER TABLE groups DROP FOREIGN KEY FK_F06D3970727ACA70');
        $this->addSql('ALTER TABLE groups ADD CONSTRAINT FK_F06D3970A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE groups ADD CONSTRAINT FK_F06D3970BC754EFF FOREIGN KEY (local_state) REFERENCES states (code)');
        $this->addSql('ALTER TABLE groups ADD CONSTRAINT FK_F06D3970727ACA70 FOREIGN KEY (parent_id) REFERENCES groups (id)');
    }
}
