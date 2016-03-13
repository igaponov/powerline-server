<?php

namespace Application\Migrations;

use Civix\CoreBundle\Service\PhoneNumberNormalizer;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Intl\Intl;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160306043901 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('ALTER TABLE user ADD phone_hash VARCHAR(40) NOT NULL');

        $normalizer = new PhoneNumberNormalizer(
            PhoneNumberUtil::getInstance(),
            Intl::getRegionBundle()
        );
        $sql = '';
        $stmt = $this->connection->query('SELECT id, phone, country FROM user WHERE phone AND country IS NOT NULL');
        while ($row = $stmt->fetch()) {
            try {
                $phone = $normalizer->normalize($row['phone'], $row['country']);
                if ($row['phone'] != $phone) {
                    $sql .= sprintf(" WHEN id = %d THEN '%s'", $row['id'], $phone);
                }
            } catch (\Exception $e) {
                $this->write('<warning>'.$e->getMessage().'</warning>');
            }
        }
        if ($sql) {
            $this->addSql('UPDATE user SET phone = CASE '.$sql.' ELSE phone END;');
        }
        $this->addSql('UPDATE user SET phone_hash = SHA1(phone)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('ALTER TABLE user DROP phone_hash');
    }
}
