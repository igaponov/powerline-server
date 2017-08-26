<?php

namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\Poll;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\UserPetition;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

class DoctrineSubscriber implements EventSubscriber
{
    private const CLASSES = [
        Post\Comment::class,
        Poll\Comment::class,
        UserPetition\Comment::class,
    ];

    public function getSubscribedEvents(): array
    {
        return [
            Events::loadClassMetadata,
        ];
    }

    /**
     * @param LoadClassMetadataEventArgs $eventArgs
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        /** @var ClassMetadataInfo $metadata */
        $metadata = $eventArgs->getClassMetadata();

        if (!in_array($metadata->getName(), self::CLASSES, true)) {
            return;
        }

        $metadata->mapManyToOne([
            'targetEntity' => $metadata->getName(),
            'fieldName' => 'parentComment',
            'inversedBy' => 'childrenComments',
            'joinColumn' => [
                'name' => 'pid',
                'referencedColumnName' => 'id',
                'onDelete' => 'CASCADE',
            ],
        ]);
        $metadata->mapOneToMany([
            'targetEntity' => $metadata->getName(),
            'fieldName' => 'childrenComments',
            'mappedBy' => 'parentComment',
        ]);
        $metadata->mapOneToMany([
            'targetEntity' => $metadata->getName() . 'Rate',
            'fieldName' => 'rates',
            'mappedBy' => 'comment',
            'fetch' => 'EXTRA_LAZY',
        ]);
    }
}