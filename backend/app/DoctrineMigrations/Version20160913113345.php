<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160913113345 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE users_follow DROP FOREIGN KEY FK_67D3CAE0A76ED395');
        $this->addSql('ALTER TABLE users_follow DROP FOREIGN KEY FK_67D3CAE0AC24F853');
        $this->addSql('
            DELETE uf FROM users_follow uf
            LEFT JOIN user u ON uf.user_id = u.id
            LEFT JOIN user f ON uf.follower_id = f.id
            WHERE uf.user_id IS NULL OR u.id IS NULL OR uf.follower_id IS NULL OR f.id IS NULL
        ');
        $this->addSql('ALTER TABLE users_follow CHANGE user_id user_id INT NOT NULL, CHANGE follower_id follower_id INT NOT NULL');
        $this->addSql('ALTER TABLE users_follow ADD CONSTRAINT FK_67D3CAE0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE users_follow ADD CONSTRAINT FK_67D3CAE0AC24F853 FOREIGN KEY (follower_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE users_follow CHANGE user_id user_id INT DEFAULT NULL, CHANGE follower_id follower_id INT DEFAULT NULL');
    }
}
