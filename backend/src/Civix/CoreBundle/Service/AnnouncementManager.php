<?php
namespace Civix\CoreBundle\Service;

use Civix\CoreBundle\Entity\Announcement;
use Civix\CoreBundle\Event\AnnouncementEvent;
use Civix\CoreBundle\Event\AnnouncementEvents;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AnnouncementManager
{
    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(EntityManager $em, EventDispatcherInterface $dispatcher)
    {
        $this->em = $em;
        $this->dispatcher = $dispatcher;
    }

    public function publish(Announcement $announcement)
    {
        $announcement->setPublishedAt(new \DateTime());

        $this->em->persist($announcement);
        $this->em->flush();

        $event = new AnnouncementEvent($announcement);
        $this->dispatcher->dispatch(AnnouncementEvents::PUBLISHED, $event);

        return $announcement;
    }
}