<?php

namespace Civix\CoreBundle\QueryFunction;

use Civix\CoreBundle\Entity\Report\MembershipReport;
use Civix\CoreBundle\Entity\Report\UserReport;
use Civix\CoreBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class AbstractUserReportQuery
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    protected function createQueryBuilder(array $permissions): QueryBuilder
    {
        $qb = $this->em->createQueryBuilder()
            ->from(User::class, 'u')
            ->leftJoin(UserReport::class, 'ur', 'WITH', 'ur.user = u.id')
            ->leftJoin(MembershipReport::class, 'mr', 'WITH', 'mr.user = u.id');
        $this->addUserAttributesToSelect($qb, $permissions);

        return $qb;
    }

    protected function addUserAttributesToSelect(QueryBuilder $qb, array $permissions)
    {
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
        $attributes = array_filter($attributes, function ($alias) use ($permissions) {
            return in_array('permissions_'.$alias, $permissions);
        });
        array_walk($attributes, [$this, 'processAttribute'], $qb);
        $qb->addSelect('
            u.bio, 
            u.slogan, 
            CASE WHEN u.facebookId IS NOT NULL THEN 1 ELSE 0 END AS facebook, 
            COALESCE(ur.followers, 0) AS followers, 
            0 AS karma, 
            mr.fields, 
            ur.representatives
        ');
    }

    public function processAttribute($alias, $attribute, QueryBuilder $qb)
    {
        $qb->addSelect("{$attribute} AS {$alias}");
    }
}