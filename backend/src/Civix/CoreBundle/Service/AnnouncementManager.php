<?php
namespace Civix\CoreBundle\Service;

use Civix\ApiBundle\Entity\AnnouncementCollection;
use Civix\CoreBundle\Entity\Announcement;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Event\AnnouncementEvent;
use Civix\CoreBundle\Event\AnnouncementEvents;
use Doctrine\Common\Collections\ArrayCollection;
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

    /**
     * Bulk update activities (mark as read)
     *
     * @param $collection
     * @param User $user
     *
     * @return array|\Civix\CoreBundle\Entity\Activity[]|ArrayCollection
     */
    public function bulkUpdate(AnnouncementCollection $collection, User $user)
    {
        $announcements = $collection->getAnnouncements();
        if ($collection->getRead()) {
            foreach ($announcements as $announcement) {
                $announcement->markAsRead($user);
                $this->em->persist($announcement);
            }
            $this->em->flush();
        }

        return $announcements;
    }
}