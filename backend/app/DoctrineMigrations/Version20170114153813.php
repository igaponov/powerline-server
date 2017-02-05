<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170114153813 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE spam_posts (post_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_4BD0EFFD4B89032C (post_id), INDEX IDX_4BD0EFFDA76ED395 (user_id), PRIMARY KEY(post_id, user_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE spam_user_petitions (userpetition_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_85023D9D6AEBE5D2 (userpetition_id), INDEX IDX_85023D9DA76ED395 (user_id), PRIMARY KEY(userpetition_id, user_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE spam_posts ADD CONSTRAINT FK_4BD0EFFD4B89032C FOREIGN KEY (post_id) REFERENCES user_posts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE spam_posts ADD CONSTRAINT FK_4BD0EFFDA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE spam_user_petitions ADD CONSTRAINT FK_85023D9D6AEBE5D2 FOREIGN KEY (userpetition_id) REFERENCES user_petitions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE spam_user_petitions ADD CONSTRAINT FK_85023D9DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE spam_posts');
        $this->addSql('DROP TABLE spam_user_petitions');
    }
}
