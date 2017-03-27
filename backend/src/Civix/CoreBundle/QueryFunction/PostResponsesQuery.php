<?php

namespace Civix\CoreBundle\QueryFunction;

use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\Report\PostResponseReport;
use Civix\CoreBundle\Entity\Report\UserReport;
use Civix\CoreBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class PostResponsesQuery
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function __invoke(Post $post)
    {
        $qb = $this->em->createQueryBuilder()
            ->select('pr.vote, ur.representatives, ur.country, ur.state, ur.locality, ur.districts, u.latitude, u.longitude')
            ->from(User::class, 'u')
            ->leftJoin(UserReport::class, 'ur', 'WITH', 'ur.user = u.id')
            ->leftJoin(PostResponseReport::class, 'pr', 'WITH', 'pr.user = u.id')
            ->where('pr.post = :post')
            ->setParameter(':post', $post->getId());


        return $qb->getQuery()->getArrayResult();
    }
}