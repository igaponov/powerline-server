<?php

namespace Civix\CoreBundle\QueryFunction;

use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\User;
use Doctrine\DBAL\Connection;

class PostVoteAnalyticsQuery
{
    /**
     * @var Connection
     */
    private $conn;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    public function __invoke(Post $post, User $user)
    {
        return [
            'total' => $this->conn->fetchAssoc(
                'SELECT
                  sum(`option` = :upvote) upvotes,
                  sum(`option` = :downvote) downvotes
                FROM post_votes v
                WHERE v.post_id = :post;',
                [
                    ':upvote' => Post\Vote::OPTION_UPVOTE,
                    ':downvote' => Post\Vote::OPTION_DOWNVOTE,
                    ':post' => $post->getId(),
                ]
            ),
            'representatives' => $this->conn->fetchAll(
                'SELECT
                  r.id, 
                  r.firstName first_name, 
                  r.lastName last_name, 
                  r.officialTitle official_title,
                  sum(`option` = :upvote) upvotes,
                  sum(`option` = :downvote) downvotes,
                  r.id IN (
                    SELECT r.id FROM cicero_representatives r
                    INNER JOIN users_districts ud ON r.district_id = ud.district_id
                    WHERE ud.user_id = :user
                  ) user,
                  r.id IN (
                    SELECT r.id FROM cicero_representatives r
                    INNER JOIN users_districts ud ON r.district_id = ud.district_id
                    WHERE ud.user_id = :author
                  ) author
                FROM post_votes v
                  INNER JOIN user u ON v.user_id = u.id
                  INNER JOIN users_districts ud ON u.id = ud.user_id
                  INNER JOIN cicero_representatives r ON r.district_id = ud.district_id
                WHERE v.post_id = :post AND v.`option` != :ignore
                GROUP BY r.id -- + temporary table :(
                ORDER BY NULL -- get rid of filesort',
                [
                    ':upvote' => Post\Vote::OPTION_UPVOTE,
                    ':downvote' => Post\Vote::OPTION_DOWNVOTE,
                    ':ignore' => Post\Vote::OPTION_IGNORE,
                    ':post' => $post->getId(),
                    ':user' => $user->getId(),
                    ':author' => $post->getUser()->getId(),
                ]
            ),
            'most_popular' => $this->conn->fetchAll('
                SELECT
                  r.id, 
                  r.firstName first_name, 
                  r.lastName last_name, 
                  r.officialTitle official_title,
                  sum(`option` = :upvote) upvotes,
                  sum(`option` = :downvote) downvotes
                FROM post_votes v
                  INNER JOIN user u ON v.user_id = u.id
                  INNER JOIN users_districts ud ON u.id = ud.user_id
                  INNER JOIN cicero_representatives r ON r.district_id = ud.district_id
                WHERE v.post_id = :post AND v.`option` != :ignore
                GROUP BY r.id -- + temporary table :(
                ORDER BY COUNT(v.id) DESC 
                LIMIT 10',
                [
                    ':upvote' => Post\Vote::OPTION_UPVOTE,
                    ':downvote' => Post\Vote::OPTION_DOWNVOTE,
                    ':ignore' => Post\Vote::OPTION_IGNORE,
                    ':post' => $post->getId(),
                ]
            )
        ];
    }
}