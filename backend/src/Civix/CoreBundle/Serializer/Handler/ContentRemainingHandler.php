<?php

namespace Civix\CoreBundle\Serializer\Handler;

use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Serializer\Type\ContentRemaining;
use Doctrine\ORM\EntityManager;
use JMS\Serializer\Context;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\JsonSerializationVisitor;
use Symfony\Component\Security\Core\SecurityContextInterface;

class ContentRemainingHandler implements SubscribingHandlerInterface
{
    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var SecurityContextInterface
     */
    private $securityContext;

    public static function getSubscribingMethods()
    {
        return [
            [
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => 'ContentRemaining',
                'method' => 'serialize',
            ],
        ];
    }

    public function __construct(EntityManager $em, SecurityContextInterface $securityContext)
    {
        $this->em = $em;
        $this->securityContext = $securityContext;
    }

    public function serialize(JsonSerializationVisitor $visitor, ContentRemaining $contentRemaining, array $type, Context $context)
    {
        $group = $contentRemaining->getGroup();
        $limit = $group->getPetitionPerMonth();
        $contentType = $contentRemaining->getContentType();
        $remaining = 0;
        switch ($contentType) {
            case 'post':
                $remaining = $limit - $this->em->getRepository(Post::class)->getCountPerMonthPostByOwner($this->securityContext->getToken()->getUser(), $group);
                break;
            case 'petition':
                $remaining = $limit - $this->em->getRepository(UserPetition::class)->getCountPerMonthPetitionByOwner($this->securityContext->getToken()->getUser(), $group);;
                break;
        }

        return $visitor->visitInteger($remaining, $type, $context);
    }
}