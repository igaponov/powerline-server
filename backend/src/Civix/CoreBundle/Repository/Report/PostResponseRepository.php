<?php

namespace Civix\CoreBundle\Repository\Report;

use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\Report\PostResponseReport;
use Civix\CoreBundle\Entity\User;
use Doctrine\ORM\EntityRepository;

class PostResponseRepository extends EntityRepository
{
    /**
     * @param User $user
     * @param Post $post
     * @return PostResponseReport|null
     */
    public function getPostResponseReport(User $user, Post $post)
    {
        return $this->createQueryBuilder('pr')
            ->where('pr.user = :user')
            ->setParameter(':user', $user->getId())
            ->andWhere('pr.post = :post')
            ->setParameter(':post', $post)
            ->getQuery()->getOneOrNullResult();
    }

    public function insertPostResponseReport(Post\Vote $vote)
    {
        $post = $vote->getPost();

        return $this->getEntityManager()->getConnection()
            ->insert('post_response_report', [
                'user_id' => $vote->getUser()->getId(),
                'post_id' => $post->getId(),
                'vote' => $vote->getOptionTitle(),
            ]);
    }

    public function deletePostResponseReport(Post\Vote $vote)
    {
        $post = $vote->getPost();

        return $this->getEntityManager()->getConnection()
            ->delete('post_response_report', [
                'user_id' => $vote->getUser()->getId(),
                'post_id' => $post->getId(),
            ]);
    }
}