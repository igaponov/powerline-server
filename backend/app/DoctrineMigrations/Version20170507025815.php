<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170507025815 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE aws_ses_monitor_deliveries (id INT AUTO_INCREMENT NOT NULL, mail_message INT DEFAULT NULL, email_address VARCHAR(255) NOT NULL, delivered_on DATETIME NOT NULL, processing_time_millis INT NOT NULL, smtp_response VARCHAR(255) NOT NULL, reporting_mta VARCHAR(255) DEFAULT NULL, INDEX IDX_ABE51B8F6C00B110 (mail_message), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE aws_ses_monitor_bounces (id INT AUTO_INCREMENT NOT NULL, mail_message INT DEFAULT NULL, email_address VARCHAR(255) NOT NULL, bounced_on DATETIME NOT NULL, type VARCHAR(255) NOT NULL, sub_type VARCHAR(255) NOT NULL, feedback_id VARCHAR(255) NOT NULL, reporting_mta VARCHAR(255) DEFAULT NULL, action LONGTEXT DEFAULT NULL, status VARCHAR(255) DEFAULT NULL, diagnostic_code LONGTEXT DEFAULT NULL, INDEX IDX_BEC114AB6C00B110 (mail_message), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE aws_ses_monitor_email_statuses (email_address VARCHAR(255) NOT NULL, hard_bounces_count INT NOT NULL, soft_bounces_count INT NOT NULL, last_bounce_type VARCHAR(255) DEFAULT NULL, last_time_bounced DATETIME DEFAULT NULL, complaints_count INT NOT NULL, last_time_complained DATETIME DEFAULT NULL, deliveries_count INT NOT NULL, last_time_delivered DATETIME DEFAULT NULL, INDEX email_addresses (email_address), PRIMARY KEY(email_address)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE aws_ses_monitor_complaints (id INT AUTO_INCREMENT NOT NULL, mail_message INT DEFAULT NULL, email_address VARCHAR(255) NOT NULL, complained_on DATETIME NOT NULL, feedback_id VARCHAR(255) NOT NULL, user_agent VARCHAR(255) DEFAULT NULL, complaint_feedback_type VARCHAR(255) DEFAULT NULL, arrival_date DATETIME DEFAULT NULL, INDEX IDX_64B831DD6C00B110 (mail_message), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE aws_ses_monitor_messages (id INT AUTO_INCREMENT NOT NULL, message_id VARCHAR(255) NOT NULL, sent_on DATETIME NOT NULL, sent_from VARCHAR(255) NOT NULL, source_arn VARCHAR(255) NOT NULL, sending_account_id VARCHAR(255) NOT NULL, headers LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', common_headers LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE aws_ses_monitor_deliveries ADD CONSTRAINT FK_ABE51B8F6C00B110 FOREIGN KEY (mail_message) REFERENCES aws_ses_monitor_messages (id)');
        $this->addSql('ALTER TABLE aws_ses_monitor_bounces ADD CONSTRAINT FK_BEC114AB6C00B110 FOREIGN KEY (mail_message) REFERENCES aws_ses_monitor_messages (id)');
        $this->addSql('ALTER TABLE aws_ses_monitor_complaints ADD CONSTRAINT FK_64B831DD6C00B110 FOREIGN KEY (mail_message) REFERENCES aws_ses_monitor_messages (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE aws_ses_monitor_deliveries DROP FOREIGN KEY FK_ABE51B8F6C00B110');
        $this->addSql('ALTER TABLE aws_ses_monitor_bounces DROP FOREIGN KEY FK_BEC114AB6C00B110');
        $this->addSql('ALTER TABLE aws_ses_monitor_complaints DROP FOREIGN KEY FK_64B831DD6C00B110');
        $this->addSql('DROP TABLE aws_ses_monitor_deliveries');
        $this->addSql('DROP TABLE aws_ses_monitor_bounces');
        $this->addSql('DROP TABLE aws_ses_monitor_email_statuses');
        $this->addSql('DROP TABLE aws_ses_monitor_complaints');
        $this->addSql('DROP TABLE aws_ses_monitor_messages');
    }
}
