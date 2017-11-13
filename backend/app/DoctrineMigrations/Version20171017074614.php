<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171017074614 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_representatives DROP FOREIGN KEY FK_FC93E535F5D4C963');
        $this->addSql('DROP INDEX UNIQ_FC93E535F5D4C963 ON user_representatives');
        $this->addSql('ALTER TABLE user_representatives DROP FOREIGN KEY FK_FC93E5352627DBBD');
        $this->addSql('ALTER TABLE user_representatives DROP FOREIGN KEY FK_FC93E5357226D8C1');
        $this->addSql('ALTER TABLE user_representatives DROP FOREIGN KEY FK_FC93E535A393D2FB');
        $this->addSql('ALTER TABLE user_representatives DROP FOREIGN KEY FK_FC93E535A76ED395');
        $this->addSql('ALTER TABLE user_representatives DROP FOREIGN KEY FK_FC93E535B08FA272');
        $this->addSql('ALTER TABLE user_representatives CHANGE cicerorepresentative_id representative_id INT DEFAULT NULL');
        $this->addSql('DROP INDEX idx_fc93e535a76ed395 ON user_representatives');
        $this->addSql('CREATE INDEX IDX_EE4F89BAA76ED395 ON user_representatives (user_id)');
        $this->addSql('DROP INDEX idx_fc93e5357226d8c1 ON user_representatives');
        $this->addSql('CREATE INDEX IDX_EE4F89BA7226D8C1 ON user_representatives (local_group)');
        $this->addSql('DROP INDEX idx_fc93e535a393d2fb ON user_representatives');
        $this->addSql('CREATE INDEX IDX_EE4F89BAA393D2FB ON user_representatives (state)');
        $this->addSql('DROP INDEX idx_fc93e535b08fa272 ON user_representatives');
        $this->addSql('CREATE INDEX IDX_EE4F89BAB08FA272 ON user_representatives (district_id)');
        $this->addSql('DROP INDEX uniq_fc93e5352627dbbd ON user_representatives');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_EE4F89BA2627DBBD ON user_representatives (stripeAccount_id)');
        $this->addSql('DROP INDEX uniq_fc93e535a76ed3957226d8c1 ON user_representatives');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_EE4F89BAA76ED3957226D8C1 ON user_representatives (user_id, local_group)');
        $this->addSql('ALTER TABLE user_representatives ADD CONSTRAINT FK_FC93E5352627DBBD FOREIGN KEY (stripeAccount_id) REFERENCES stripe_accounts (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE user_representatives ADD CONSTRAINT FK_FC93E5357226D8C1 FOREIGN KEY (local_group) REFERENCES groups (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_representatives ADD CONSTRAINT FK_FC93E535A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_representatives ADD CONSTRAINT FK_FC93E535B08FA272 FOREIGN KEY (district_id) REFERENCES districts (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE user_representatives ADD CONSTRAINT FK_EE4F89BAA393D2FB FOREIGN KEY (state) REFERENCES states (code) ON DELETE SET NULL');

        $this->addSql('RENAME TABLE cicero_representatives TO representatives');
        $this->addSql('ALTER TABLE representatives DROP FOREIGN KEY FK_CB0237A8A393D2FB');
        $this->addSql('ALTER TABLE representatives DROP FOREIGN KEY FK_CB0237A8B08FA272');
        $this->addSql('ALTER TABLE representatives ADD cicero_id INT DEFAULT NULL, CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('DROP INDEX idx_cb0237a8a393d2fb ON representatives');
        $this->addSql('CREATE INDEX IDX_FC93E535A393D2FB ON representatives (state)');
        $this->addSql('DROP INDEX idx_cb0237a8b08fa272 ON representatives');
        $this->addSql('CREATE INDEX IDX_FC93E535B08FA272 ON representatives (district_id)');
        $this->addSql('ALTER TABLE representatives ADD CONSTRAINT FK_CB0237A8A393D2FB FOREIGN KEY (state) REFERENCES states (code) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE representatives ADD CONSTRAINT FK_CB0237A8B08FA272 FOREIGN KEY (district_id) REFERENCES districts (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE user_representatives ADD CONSTRAINT FK_EE4F89BAFC3FF006 FOREIGN KEY (representative_id) REFERENCES representatives (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_EE4F89BAFC3FF006 ON user_representatives (representative_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_representatives DROP FOREIGN KEY FK_EE4F89BAFC3FF006');
        $this->addSql('DROP INDEX UNIQ_EE4F89BAFC3FF006 ON user_representatives');
        $this->addSql('ALTER TABLE user_representatives DROP FOREIGN KEY FK_EE4F89BAA76ED395');
        $this->addSql('ALTER TABLE user_representatives DROP FOREIGN KEY FK_EE4F89BA7226D8C1');
        $this->addSql('ALTER TABLE user_representatives DROP FOREIGN KEY FK_EE4F89BAA393D2FB');
        $this->addSql('ALTER TABLE user_representatives DROP FOREIGN KEY FK_EE4F89BAB08FA272');
        $this->addSql('ALTER TABLE user_representatives DROP FOREIGN KEY FK_EE4F89BA2627DBBD');
        $this->addSql('ALTER TABLE user_representatives CHANGE representative_id ciceroRepresentative_id INT DEFAULT NULL');
        $this->addSql('DROP INDEX uniq_ee4f89baa76ed3957226d8c1 ON user_representatives');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FC93E535A76ED3957226D8C1 ON user_representatives (user_id, local_group)');
        $this->addSql('DROP INDEX uniq_ee4f89ba2627dbbd ON user_representatives');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FC93E5352627DBBD ON user_representatives (stripeAccount_id)');
        $this->addSql('DROP INDEX idx_ee4f89baa393d2fb ON user_representatives');
        $this->addSql('CREATE INDEX IDX_FC93E535A393D2FB ON user_representatives (state)');
        $this->addSql('DROP INDEX idx_ee4f89bab08fa272 ON user_representatives');
        $this->addSql('CREATE INDEX IDX_FC93E535B08FA272 ON user_representatives (district_id)');
        $this->addSql('DROP INDEX idx_ee4f89ba7226d8c1 ON user_representatives');
        $this->addSql('CREATE INDEX IDX_FC93E5357226D8C1 ON user_representatives (local_group)');
        $this->addSql('DROP INDEX idx_ee4f89baa76ed395 ON user_representatives');
        $this->addSql('CREATE INDEX IDX_FC93E535A76ED395 ON user_representatives (user_id)');
        $this->addSql('ALTER TABLE user_representatives ADD CONSTRAINT FK_EE4F89BAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_representatives ADD CONSTRAINT FK_EE4F89BA7226D8C1 FOREIGN KEY (local_group) REFERENCES groups (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_representatives ADD CONSTRAINT FK_EE4F89BAA393D2FB FOREIGN KEY (state) REFERENCES states (code) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE user_representatives ADD CONSTRAINT FK_EE4F89BAB08FA272 FOREIGN KEY (district_id) REFERENCES districts (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE user_representatives ADD CONSTRAINT FK_EE4F89BA2627DBBD FOREIGN KEY (stripeAccount_id) REFERENCES stripe_accounts (id) ON DELETE SET NULL');

        $this->addSql('ALTER TABLE representatives DROP FOREIGN KEY FK_FC93E535A393D2FB');
        $this->addSql('ALTER TABLE representatives DROP FOREIGN KEY FK_FC93E535B08FA272');
        $this->addSql('ALTER TABLE representatives DROP cicero_id, CHANGE id id INT NOT NULL');
        $this->addSql('DROP INDEX idx_fc93e535a393d2fb ON representatives');
        $this->addSql('CREATE INDEX IDX_CB0237A8A393D2FB ON representatives (state)');
        $this->addSql('DROP INDEX idx_fc93e535b08fa272 ON representatives');
        $this->addSql('CREATE INDEX IDX_CB0237A8B08FA272 ON representatives (district_id)');
        $this->addSql('ALTER TABLE representatives ADD CONSTRAINT FK_FC93E535A393D2FB FOREIGN KEY (state) REFERENCES states (code) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE representatives ADD CONSTRAINT FK_FC93E535B08FA272 FOREIGN KEY (district_id) REFERENCES districts (id) ON DELETE CASCADE');
        $this->addSql('RENAME TABLE representatives TO cicero_representatives');

        $this->addSql('ALTER TABLE user_representatives ADD CONSTRAINT FK_FC93E535F5D4C963 FOREIGN KEY (ciceroRepresentative_id) REFERENCES cicero_representatives (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FC93E535F5D4C963 ON user_representatives (ciceroRepresentative_id)');
    }
}
