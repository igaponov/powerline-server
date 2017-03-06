<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170226144304 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE discount_code_uses (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, created_at DATETIME NOT NULL, discount_code_id INT NOT NULL, INDEX IDX_ACA504E37F140C37 (discount_code_id), INDEX IDX_ACA504E3A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC');
        $this->addSql('CREATE TABLE discount_codes (id INT AUTO_INCREMENT NOT NULL, owner_id INT DEFAULT NULL, code VARCHAR(12) NOT NULL, original_code VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_CE8719DA77153098 (code), UNIQUE INDEX UNIQ_CE8719DA7E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC');
        $this->addSql('ALTER TABLE discount_code_uses ADD CONSTRAINT FK_ACA504E37F140C37 FOREIGN KEY (discount_code_id) REFERENCES discount_codes (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE discount_code_uses ADD CONSTRAINT FK_ACA504E3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE discount_codes ADD CONSTRAINT FK_CE8719DA7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('INSERT INTO discount_codes(owner_id, code, original_code, created_at) SELECT id, lpad(conv(floor(rand()*pow(36,12)), 10, 36), 12, 0), ?, current_timestamp FROM user', [$this->container->getParameter('stripe_referral_code')]);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE discount_code_uses DROP FOREIGN KEY FK_ACA504E37F140C37');
        $this->addSql('DROP TABLE discount_code_uses');
        $this->addSql('DROP TABLE discount_codes');
    }
}
