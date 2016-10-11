<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161011062846 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $conn = $this->connection;
        $this->abortIf(
            $conn->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $stmt = $conn->prepare('SELECT id, following_id, target FROM social_activities WHERE target NOT LIKE ?');
        $stmt->execute(['%full_name%']);
        while ($row = $stmt->fetch()) {
            $target = unserialize($row['target']);
            if (isset($target['first_name'])) {
                $target['full_name'] = $target['first_name'].(isset($target['last_name']) ? ' '.$target['last_name'] : '');
            } elseif ($row['following_id']) {
                $user = $conn->fetchAssoc('SELECT firstName AS fname, lastName AS lname FROM user WHERE id = ?', [$row['following_id']]);
                if ($user) {
                    $target['full_name'] = $user['fname'].' '.$user['lname'];
                } else {
                    $target['full_name'] = 'Test User';
                }
            }
            $this->addSql('UPDATE social_activities SET target = ? WHERE id = ?', [serialize($target), $row['id']]);
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
    }
}
