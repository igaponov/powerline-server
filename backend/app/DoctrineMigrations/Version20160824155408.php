<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160824155408 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE subscriptions DROP FOREIGN KEY FK_4778A014ACC9A20');
        $this->addSql('ALTER TABLE balanced_payment DROP FOREIGN KEY FK_9E848ACF6A7DC786');
        $this->addSql('ALTER TABLE balanced_payment DROP FOREIGN KEY FK_9E848ACFF8050BAA');
        $this->addSql('ALTER TABLE bank_accounts DROP FOREIGN KEY FK_FB88842B9395C3F3');
        $this->addSql('ALTER TABLE cards DROP FOREIGN KEY FK_4C258FD9395C3F3');
        $this->addSql('ALTER TABLE discounts_codes_history DROP FOREIGN KEY FK_D17C9E879395C3F3');
        $this->addSql('ALTER TABLE payments_transaction DROP FOREIGN KEY FK_63BEF23B9395C3F3');
        $this->addSql('ALTER TABLE discounts_codes_history DROP FOREIGN KEY FK_D17C9E8727DAFE17');
        $this->addSql('DROP TABLE balanced_payment');
        $this->addSql('DROP TABLE bank_accounts');
        $this->addSql('DROP TABLE cards');
        $this->addSql('DROP TABLE customers');
        $this->addSql('DROP TABLE discounts_codes');
        $this->addSql('DROP TABLE discounts_codes_history');
        $this->addSql('DROP TABLE orders');
        $this->addSql('DROP INDEX IDX_4778A014ACC9A20 ON subscriptions');
        $this->addSql('ALTER TABLE subscriptions DROP card_id');
        $this->addSql('DROP INDEX IDX_63BEF23B9395C3F3 ON payments_transaction');
        $this->addSql('ALTER TABLE payments_transaction DROP customer_id');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE balanced_payment (id INT AUTO_INCREMENT NOT NULL, to_user INT DEFAULT NULL, from_user INT NOT NULL, public_id VARCHAR(255) NOT NULL, amount DOUBLE PRECISION NOT NULL, currency VARCHAR(3) NOT NULL, reference VARCHAR(255) DEFAULT NULL, data LONGTEXT NOT NULL, state VARCHAR(10) NOT NULL, updated_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, balanced_uri VARCHAR(255) DEFAULT NULL, question_id INT DEFAULT NULL, order_id VARCHAR(255) DEFAULT NULL, paid_out TINYINT(1) DEFAULT NULL, INDEX IDX_9E848ACFF8050BAA (from_user), INDEX IDX_9E848ACF6A7DC786 (to_user), INDEX state (state), INDEX question_id (question_id), INDEX orderId (order_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE bank_accounts (id INT AUTO_INCREMENT NOT NULL, customer_id INT DEFAULT NULL, name VARCHAR(100) NOT NULL, verified TINYINT(1) NOT NULL, balanced_uri VARCHAR(255) NOT NULL, updated_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_FB88842B9395C3F3 (customer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cards (id INT AUTO_INCREMENT NOT NULL, customer_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, balanced_uri VARCHAR(255) NOT NULL, updated_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, number VARCHAR(4) DEFAULT NULL, INDEX IDX_4C258FD9395C3F3 (customer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE customers (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, representative_id INT DEFAULT NULL, group_id INT DEFAULT NULL, balanced_uri VARCHAR(255) DEFAULT NULL, type VARCHAR(255) NOT NULL, account_type VARCHAR(255) DEFAULT NULL, INDEX IDX_62534E21FC3FF006 (representative_id), INDEX IDX_62534E21FE54D947 (group_id), INDEX IDX_62534E21A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE discounts_codes (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(255) NOT NULL, percents INT NOT NULL, month INT NOT NULL, max_users INT NOT NULL, status SMALLINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, package_type INT DEFAULT NULL, UNIQUE INDEX UNIQ_9C8520677153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE discounts_codes_history (id INT AUTO_INCREMENT NOT NULL, code_id INT DEFAULT NULL, customer_id INT DEFAULT NULL, status SMALLINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_D17C9E8727DAFE17 (code_id), INDEX IDX_D17C9E879395C3F3 (customer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE orders (id INT AUTO_INCREMENT NOT NULL, payment_request_id INT DEFAULT NULL, balanced_uri VARCHAR(255) NOT NULL, updated_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, type VARCHAR(255) NOT NULL, public_id VARCHAR(255) DEFAULT NULL, INDEX IDX_E52FFDEE77883970 (payment_request_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE balanced_payment ADD CONSTRAINT FK_9E848ACF6A7DC786 FOREIGN KEY (to_user) REFERENCES customers (id)');
        $this->addSql('ALTER TABLE balanced_payment ADD CONSTRAINT FK_9E848ACFF8050BAA FOREIGN KEY (from_user) REFERENCES customers (id)');
        $this->addSql('ALTER TABLE bank_accounts ADD CONSTRAINT FK_FB88842B9395C3F3 FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cards ADD CONSTRAINT FK_4C258FD9395C3F3 FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE customers ADD CONSTRAINT FK_62534E21A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE customers ADD CONSTRAINT FK_62534E21FC3FF006 FOREIGN KEY (representative_id) REFERENCES representatives (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE customers ADD CONSTRAINT FK_62534E21FE54D947 FOREIGN KEY (group_id) REFERENCES groups (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE discounts_codes_history ADD CONSTRAINT FK_D17C9E8727DAFE17 FOREIGN KEY (code_id) REFERENCES discounts_codes (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE discounts_codes_history ADD CONSTRAINT FK_D17C9E879395C3F3 FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE77883970 FOREIGN KEY (payment_request_id) REFERENCES poll_questions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE payments_transaction ADD customer_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE payments_transaction ADD CONSTRAINT FK_63BEF23B9395C3F3 FOREIGN KEY (customer_id) REFERENCES customers (id)');
        $this->addSql('CREATE INDEX IDX_63BEF23B9395C3F3 ON payments_transaction (customer_id)');
        $this->addSql('ALTER TABLE subscriptions ADD card_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE subscriptions ADD CONSTRAINT FK_4778A014ACC9A20 FOREIGN KEY (card_id) REFERENCES cards (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_4778A014ACC9A20 ON subscriptions (card_id)');
    }
}
