<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160908020711 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE payments_transaction DROP FOREIGN KEY FK_63BEF23B708DC647');
        $this->addSql('
            DELETE pt FROM payments_transaction pt 
            LEFT JOIN stripe_customers sc ON pt.stripe_customer_id = sc.id
            WHERE pt.stripe_customer_id IS NULL OR sc.id IS NULL
        ');
        $this->addSql('ALTER TABLE payments_transaction CHANGE stripe_customer_id stripe_customer_id INT NOT NULL');
        $this->addSql('ALTER TABLE payments_transaction ADD CONSTRAINT FK_63BEF23B708DC647 FOREIGN KEY (stripe_customer_id) REFERENCES stripe_customers (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE payments_transaction DROP FOREIGN KEY FK_63BEF23B708DC647');
        $this->addSql('ALTER TABLE payments_transaction CHANGE stripe_customer_id stripe_customer_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE payments_transaction ADD CONSTRAINT FK_63BEF23B708DC647 FOREIGN KEY (stripe_customer_id) REFERENCES stripe_customers (id)');
    }
}
