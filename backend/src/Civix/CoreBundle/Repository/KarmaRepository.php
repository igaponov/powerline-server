<?php

namespace Civix\CoreBundle\Repository;

use Civix\CoreBundle\Entity\Karma;
use Civix\CoreBundle\Entity\User;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityRepository;

class KarmaRepository extends EntityRepository
{
    /**
     * @param User $user
     * @param int $type
     * @return Karma|object|null
     */
    public function findOneByUserAndType(User $user, int $type): ?Karma
    {
        return $this->findOneBy([
            'user' => $user,
            'type' => $type,
        ]);
    }

    public function deleteByUserAndTypeAndMetadata(User $user, int $type, array $metadata)
    {
        return $this->createQueryBuilder('k')
            ->delete()
            ->where('k.user = :user')
            ->setParameter(':user', $user)
            ->andWhere('k.type = :type')
            ->setParameter(':type', $type)
            ->andWhere('k.metadata = :metadata')
            ->setParameter(':metadata', $metadata, Type::TARRAY)
            ->getQuery()->execute();
    }
}