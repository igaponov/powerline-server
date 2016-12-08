<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161208123841 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE groups ADD stripeAccount_id VARCHAR(255) DEFAULT NULL, ADD stripeCustomer_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('
            UPDATE groups g
            LEFT JOIN stripe_accounts a ON g.id = a.group_id
            SET g.stripeAccount_id = a.stripe_id
        ');
        $this->addSql('
            UPDATE groups g
            LEFT JOIN stripe_customers c ON g.id = c.group_id
            SET g.stripeCustomer_id = c.stripe_id
        ');
        $this->addSql('ALTER TABLE representatives ADD stripeAccount_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('
            UPDATE representatives r 
            LEFT JOIN stripe_accounts a ON r.id = a.representative_id
            SET r.stripeAccount_id = a.stripe_id
        ');
        $this->addSql('ALTER TABLE user ADD stripeCustomer_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('
            UPDATE user u 
            LEFT JOIN stripe_customers c ON u.id = c.user_id
            SET u.stripeCustomer_id = c.stripe_id
        ');
        $this->addSql('ALTER TABLE stripe_charges DROP FOREIGN KEY FK_152861E0C7D66E93');
        $this->addSql('ALTER TABLE stripe_charges DROP FOREIGN KEY FK_152861E0F3AE6B51');
        $this->addSql('ALTER TABLE stripe_charges CHANGE from_customer from_customer VARCHAR(255) NOT NULL, CHANGE to_account to_account VARCHAR(255) DEFAULT NULL');
        $this->addSql('
            UPDATE stripe_charges c
            LEFT JOIN stripe_accounts a ON c.to_account = a.id
            SET c.to_account = a.stripe_id
        ');
        $this->addSql('
            UPDATE stripe_charges h
            LEFT JOIN stripe_customers c ON h.from_customer = c.id
            SET h.from_customer = c.stripe_id
        ');
        $this->addSql('ALTER TABLE payments_transaction DROP FOREIGN KEY FK_63BEF23B708DC647');
        $this->addSql('ALTER TABLE payments_transaction CHANGE stripe_customer_id stripe_customer_id VARCHAR(255) NOT NULL');
        $this->addSql('
            UPDATE payments_transaction p
            LEFT JOIN stripe_customers c ON p.stripe_customer_id = c.id
            SET p.stripe_customer_id = c.stripe_id
        ');
        $this->addSql('ALTER TABLE stripe_accounts DROP FOREIGN KEY FK_978F429FFC3FF006');
        $this->addSql('ALTER TABLE stripe_accounts DROP FOREIGN KEY FK_978F429FFE54D947');
        $this->addSql('DROP INDEX IDX_978F429FFC3FF006 ON stripe_accounts');
        $this->addSql('DROP INDEX IDX_978F429FFE54D947 ON stripe_accounts');
        $this->addSql('ALTER TABLE stripe_accounts DROP representative_id, DROP group_id, DROP type, CHANGE id id VARCHAR(255) NOT NULL');
        $this->addSql('UPDATE stripe_accounts SET id = stripe_id');
        $this->addSql('ALTER TABLE stripe_accounts DROP stripe_id');
        $this->addSql('ALTER TABLE stripe_customers DROP FOREIGN KEY FK_DDDE68EBA76ED395');
        $this->addSql('ALTER TABLE stripe_customers DROP FOREIGN KEY FK_DDDE68EBFE54D947');
        $this->addSql('DROP INDEX IDX_DDDE68EBFE54D947 ON stripe_customers');
        $this->addSql('DROP INDEX IDX_DDDE68EBA76ED395 ON stripe_customers');
        $this->addSql('ALTER TABLE stripe_customers DROP user_id, DROP group_id, DROP type, CHANGE id id VARCHAR(255) NOT NULL');
        $this->addSql('UPDATE stripe_customers SET id = stripe_id');
        $this->addSql('ALTER TABLE stripe_customers DROP stripe_id');
        $this->addSql('ALTER TABLE stripe_charges ADD CONSTRAINT FK_152861E0C7D66E93 FOREIGN KEY (from_customer) REFERENCES stripe_customers (id)');
        $this->addSql('ALTER TABLE stripe_charges ADD CONSTRAINT FK_152861E0F3AE6B51 FOREIGN KEY (to_account) REFERENCES stripe_accounts (id)');
        $this->addSql('ALTER TABLE payments_transaction ADD CONSTRAINT FK_63BEF23B708DC647 FOREIGN KEY (stripe_customer_id) REFERENCES stripe_customers (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE representatives ADD CONSTRAINT FK_FC93E5352627DBBD FOREIGN KEY (stripeAccount_id) REFERENCES stripe_accounts (id) ON DELETE SET NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FC93E5352627DBBD ON representatives (stripeAccount_id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649D4C1AD4 FOREIGN KEY (stripeCustomer_id) REFERENCES stripe_customers (id) ON DELETE SET NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649D4C1AD4 ON user (stripeCustomer_id)');
        $this->addSql('ALTER TABLE groups ADD CONSTRAINT FK_F06D39702627DBBD FOREIGN KEY (stripeAccount_id) REFERENCES stripe_accounts (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE groups ADD CONSTRAINT FK_F06D3970D4C1AD4 FOREIGN KEY (stripeCustomer_id) REFERENCES stripe_customers (id) ON DELETE SET NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F06D39702627DBBD ON groups (stripeAccount_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F06D3970D4C1AD4 ON groups (stripeCustomer_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE groups DROP FOREIGN KEY FK_F06D39702627DBBD');
        $this->addSql('ALTER TABLE groups DROP FOREIGN KEY FK_F06D3970D4C1AD4');
        $this->addSql('DROP INDEX UNIQ_F06D39702627DBBD ON groups');
        $this->addSql('DROP INDEX UNIQ_F06D3970D4C1AD4 ON groups');
        $this->addSql('ALTER TABLE groups DROP stripeAccount_id, DROP stripeCustomer_id');
        $this->addSql('ALTER TABLE payments_transaction CHANGE stripe_customer_id stripe_customer_id INT NOT NULL');
        $this->addSql('ALTER TABLE representatives DROP FOREIGN KEY FK_FC93E5352627DBBD');
        $this->addSql('DROP INDEX UNIQ_FC93E5352627DBBD ON representatives');
        $this->addSql('ALTER TABLE representatives DROP stripeAccount_id');
        $this->addSql('ALTER TABLE stripe_accounts ADD representative_id INT DEFAULT NULL, ADD group_id INT DEFAULT NULL, ADD stripe_id VARCHAR(255) NOT NULL, ADD type VARCHAR(255) NOT NULL, CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE stripe_accounts ADD CONSTRAINT FK_978F429FFC3FF006 FOREIGN KEY (representative_id) REFERENCES representatives (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stripe_accounts ADD CONSTRAINT FK_978F429FFE54D947 FOREIGN KEY (group_id) REFERENCES groups (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_978F429FFC3FF006 ON stripe_accounts (representative_id)');
        $this->addSql('CREATE INDEX IDX_978F429FFE54D947 ON stripe_accounts (group_id)');
        $this->addSql('ALTER TABLE stripe_charges CHANGE from_customer from_customer INT NOT NULL, CHANGE to_account to_account INT DEFAULT NULL');
        $this->addSql('ALTER TABLE stripe_customers ADD user_id INT DEFAULT NULL, ADD group_id INT DEFAULT NULL, ADD stripe_id VARCHAR(255) NOT NULL, ADD type VARCHAR(255) NOT NULL, CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE stripe_customers ADD CONSTRAINT FK_DDDE68EBA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stripe_customers ADD CONSTRAINT FK_DDDE68EBFE54D947 FOREIGN KEY (group_id) REFERENCES groups (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_DDDE68EBFE54D947 ON stripe_customers (group_id)');
        $this->addSql('CREATE INDEX IDX_DDDE68EBA76ED395 ON stripe_customers (user_id)');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649D4C1AD4');
        $this->addSql('DROP INDEX UNIQ_8D93D649D4C1AD4 ON user');
        $this->addSql('ALTER TABLE user DROP stripeCustomer_id');
    }
}
