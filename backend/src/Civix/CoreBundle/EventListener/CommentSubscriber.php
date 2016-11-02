<?php
namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Event\CommentEvents;
use Civix\CoreBundle\Event\RateEvent;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CommentSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManager
     */
    private $em;

    public static function getSubscribedEvents()
    {
        return [
            CommentEvents::RATE => 'updateCommentRate',
        ];
    }

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function updateCommentRate(RateEvent $event)
    {
        $rate = $event->getRate();
        $comment = $rate->getComment();
        $meta = $this->em->getClassMetadata(get_class($comment));
        $associationClass = $meta->getAssociationTargetClass('rates');
        /** @var \Civix\CoreBundle\Repository\CommentRateRepository $repository */
        $repository = $this->em->getRepository($associationClass);
        $stats = $repository->getRateStatistics($comment);
        $comment->setRatesCount($stats['rateCount']);
        $comment->setRateSum($stats['rateSum']);
        $this->em->persist($comment);
        $this->em->flush();
    }
}