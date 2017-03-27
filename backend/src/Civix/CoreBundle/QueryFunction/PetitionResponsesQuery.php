<?php

namespace Civix\CoreBundle\QueryFunction;

use Civix\CoreBundle\Entity\Report\PetitionResponseReport;
use Civix\CoreBundle\Entity\Report\UserReport;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserPetition;
use Doctrine\ORM\EntityManagerInterface;

class PetitionResponsesQuery
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function __invoke(UserPetition $petition)
    {
        $qb = $this->em->createQueryBuilder()
            ->select('ur.representatives, ur.country, ur.state, ur.locality, ur.districts, u.latitude, u.longitude')
            ->from(User::class, 'u')
            ->leftJoin(UserReport::class, 'ur', 'WITH', 'ur.user = u.id')
            ->leftJoin(PetitionResponseReport::class, 'pr', 'WITH', 'pr.user = u.id')
            ->where('pr.petition = :petition')
            ->setParameter(':petition', $petition->getId());


        return $qb->getQuery()->getArrayResult();
    }
}