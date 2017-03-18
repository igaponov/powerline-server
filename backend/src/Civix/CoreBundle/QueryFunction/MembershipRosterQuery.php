<?php

namespace Civix\CoreBundle\QueryFunction;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Report\MembershipReport;
use Civix\CoreBundle\Entity\Report\UserReport;
use Civix\CoreBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;

class MembershipRosterQuery
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function __invoke(Group $group) : array
    {
        $permissions = $group->getRequiredPermissions();
        $qb = $this->em->createQueryBuilder()
            ->from(User::class, 'u')
            ->leftJoin(UserReport::class, 'ur', 'WITH', 'ur.user = u.id')
            ->leftJoin(MembershipReport::class, 'mr', 'WITH', 'mr.user = u.id')
            ->where('mr.group = :group')
            ->setParameter(':group', $group->getId())
            ->groupBy('u.id');
        $attributes = [
            "CONCAT(u.firstName, ' ', u.lastName)" => 'name',
            "CONCAT(u.address1, ' ', u.address2)" => 'address',
            'u.city' => 'city',
            'u.state' => 'state',
            'u.country' => 'country',
            'u.zip' => 'zip_code',
            'u.email' => 'email',
            'u.phone' => 'phone'
        ];
        foreach ($attributes as $attribute => $alias) {
            if (!in_array('permissions_'.$alias, $permissions)) {
                continue;
            }
            if (!is_string($attribute)) {
                $attribute = $alias;
            }
            $qb->addSelect("{$attribute} AS {$alias}");
        }
        $qb->addSelect('u.bio, u.slogan, CASE WHEN u.facebookId IS NOT NULL THEN 1 ELSE 0 END AS facebook, ur.followers, 0 AS karma, mr.fields, ur.representatives');

        return $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);
    }
}