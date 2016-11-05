<?php

namespace Application\Migrations;

use Civix\CoreBundle\Entity\SocialActivity;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161105114625 extends AbstractMigration
{
    private $types = [
        SocialActivity::TYPE_FOLLOW_POLL_COMMENTED,
        SocialActivity::TYPE_FOLLOW_POST_COMMENTED,
        SocialActivity::TYPE_FOLLOW_USER_PETITION_COMMENTED,
    ];

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $rows = $this->connection->fetchAll('
            SELECT id, target FROM social_activities sa
            WHERE type IN (?,?,?) and following_id IS NULL
        ', $this->types);
        foreach ($rows as $row) {
            $data = unserialize($row['target']);
            if (isset($data['full_name'])) {
                $user = $this->connection->fetchColumn(
                    'SELECT id FROM user WHERE CONCAT_WS(" ", firstName, lastName) = ?',
                    [$data['full_name']]
                );
                if ($user) {
                    $this->addSql('UPDATE social_activities SET following_id = ? WHERE id = ?', [$user, $row['id']]);
                }
            }
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE social_activities SET following_id = NULL WHERE type IN (?,?,?)', $this->types);
    }
}
