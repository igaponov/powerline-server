<?php

namespace Civix\CoreBundle\Repository\Report;

use Civix\CoreBundle\Entity\Report\PetitionResponseReport;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserPetition;
use Doctrine\ORM\EntityRepository;

class PetitionResponseRepository extends EntityRepository
{
    /**
     * @param User $user
     * @param UserPetition $petition
     * @return PetitionResponseReport|null
     */
    public function getPetitionResponseReport(User $user, UserPetition $petition)
    {
        return $this->createQueryBuilder('pr')
            ->where('pr.user = :user')
            ->setParameter(':user', $user->getId())
            ->andWhere('pr.petition = :petition')
            ->setParameter(':petition', $petition)
            ->getQuery()->getOneOrNullResult();
    }

    public function insertPetitionResponseReport(UserPetition\Signature $signature)
    {
        $petition = $signature->getPetition();

        return $this->getEntityManager()->getConnection()
            ->insert('petition_response_report', [
                'user_id' => $signature->getUser()->getId(),
                'petition_id' => $petition->getId(),
            ]);
    }

    public function deletePetitionResponseReport(UserPetition\Signature $signature)
    {
        $petition = $signature->getPetition();

        return $this->getEntityManager()->getConnection()
            ->delete('petition_response_report', [
                'user_id' => $signature->getUser()->getId(),
                'petition_id' => $petition->getId(),
            ]);
    }
}