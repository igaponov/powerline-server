<?php
namespace Civix\CoreBundle\Service;

use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserFollow;
use Civix\CoreBundle\Event\UserEvents;
use Civix\CoreBundle\Event\UserFollowEvent;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class FollowerManager
{
    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(
        EntityManager $em,
        EventDispatcherInterface $dispatcher
    ) {
        $this->em = $em;
        $this->dispatcher = $dispatcher;
    }

    public function follow(User $user, User $follower)
    {
        /* @var UserFollow $userFollow */
        $userFollow = $this->em->getRepository(UserFollow::class)
            ->findOneBy([
                'user' => $user,
                'follower' => $follower
            ]);
        if (!$userFollow) {
            $userFollow = new UserFollow();
            $userFollow->setFollower($follower)
                ->setStatus(UserFollow::STATUS_PENDING)
                ->setUser($user);
        }

        $this->em->persist($userFollow);
        $this->em->flush();

        $event = new UserFollowEvent($userFollow);
        $this->dispatcher->dispatch(UserEvents::FOLLOWED, $event);
    }

    public function unfollow(UserFollow $userFollow)
    {
        $this->em->remove($userFollow);
        $this->em->flush();
    }

    public function approve(UserFollow $userFollow)
    {
        if (!$userFollow->getDateApproval()) {
            $userFollow->approve();
            $this->em->persist($userFollow);
            $this->em->flush();
        }
    }
}