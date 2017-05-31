<?php

namespace Civix\CoreBundle\Service\User;

use Civix\CoreBundle\Entity\DeferredInvites;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\Report\UserReport;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Event\AvatarEvent;
use Civix\CoreBundle\Event\AvatarEvents;
use Civix\CoreBundle\Event\UserEvent;
use Civix\CoreBundle\Event\UserEvents;
use Civix\CoreBundle\Service\CiceroApi;
use Civix\CoreBundle\Service\Group\GroupManager;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class UserManager
{
    const USER_RESET_PASSWORD_INTERVAL_HOURS = 24;

    private $entityManager;
    private $ciceroApi;
    private $groupManager;
    private $kernelRootDir;
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(
        EntityManager $entityManager,
        CiceroApi $ciceroApi,
        GroupManager $groupManager,
        EventDispatcherInterface $dispatcher,
        $kernelRootDir
    ) {
        $this->entityManager = $entityManager;
        $this->ciceroApi = $ciceroApi;
        $this->groupManager = $groupManager;
        $this->dispatcher = $dispatcher;
        $this->kernelRootDir = $kernelRootDir;
    }

    public function updateDistrictsIds(User $user)
    {
        $representatives = $this->ciceroApi->getRepresentativesByLocation(
            $user->getLineAddress(),
            $user->getCity(),
            $user->getState(),
            $user->getCountry()
        );
        if (!empty($representatives)) {
            $user->getDistricts()->clear();

            $representativeList = $districtList = [];
            foreach ($representatives as $representative) {
                $representativeList[] = $representative->getOfficialTitle().' '.$representative->getFullName();
                $districtList[] = $representative->getDistrict()->getLabel();
                $user->addDistrict($representative->getDistrict());
            }
            $this->entityManager->getRepository(UserReport::class)
                ->upsertUserReport($user, $user->getFollowers()->count(), $representativeList, null, null, null, array_unique($districtList));

            $user->setUpdateProfileAt(new \DateTime());
        }

        return $user;
    }

    public function updateSettings(User $user, User $userWithSettings)
    {
        $settings = array(
            'DoNotDisturb', 'IsNotifQuestions', 'IsNotifDiscussions',
            'IsNotifMessages', 'IsNotifMicroFollowing', 'IsNotifMicroGroup',
            'IsNotifScheduled', 'IsNotifOwnPostChanged', 'ScheduledFrom', 'ScheduledTo'
        );

        foreach ($settings as $setting) {
            $setMethod = 'set'.$setting;
            $getMethod = 'get'.$setting;
            $user->$setMethod($userWithSettings->$getMethod());
        }

        return $user;
    }

    public function checkResetInterval(User $user)
    {
        $lastResetDate = $user->getResetPasswordAt();
        if (is_null($lastResetDate)) {
            return true;
        }

        $currentDate = new \DateTime();
        $resetIntervalHours = ($currentDate->getTimestamp() - $lastResetDate->getTimestamp()) / 3600;

        if ($resetIntervalHours >= 24) {
            return true;
        }

        return false;
    }

    public function subscribeToPetition(User $user, UserPetition $petition)
    {
        if (!$user->getPetitionSubscriptions()->contains($petition)) {
            $user->addPetitionSubscription($petition);
            $this->entityManager->persist($petition);
            $this->entityManager->flush();
        }
    }

    public function unsubscribeFromPetition(User $user, UserPetition $petition)
    {
        if ($user->getPetitionSubscriptions()->contains($petition)) {
            $user->removePetitionSubscription($petition);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
    }

    public function subscribeToPost(User $user, Post $post)
    {
        if (!$user->getPostSubscriptions()->contains($post)) {
            $user->addPostSubscription($post);
            $this->entityManager->persist($post);
            $this->entityManager->flush();
        }
    }

    public function unsubscribeFromPost(User $user, Post $post)
    {
        if ($user->getPostSubscriptions()->contains($post)) {
            $user->removePostSubscription($post);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
    }

    public function subscribeToPoll(User $user, Question $poll)
    {
        if (!$user->getPollSubscriptions()->contains($poll)) {
            $user->addPollSubscription($poll);
            $this->entityManager->persist($poll);
            $this->entityManager->flush();
        }
    }

    public function unsubscribeFromPoll(User $user, Question $poll)
    {
        if ($user->getPollSubscriptions()->contains($poll)) {
            $user->removePollSubscription($poll);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
    }

    public function register(User $user)
    {
        $user->generateToken();
        $event = new AvatarEvent($user);
        $this->dispatcher->dispatch(AvatarEvents::CHANGE, $event);

        /** @var DeferredInvites[] $deferredInvites */
        $deferredInvites = $this->entityManager
            ->getRepository('CivixCoreBundle:DeferredInvites')
            ->checkEmail($user->getEmail());

        if (!empty($deferredInvites)) {
            foreach ($deferredInvites as $invite) {
                $user->addInvite($invite->getGroup());
                $invite->setStatus(DeferredInvites::STATUS_INACTIVE);
                $this->entityManager->persist($user);
                $this->entityManager->persist($invite);
            }
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->updateDistrictsIds($user);

        $event = new UserEvent($user);
        $this->dispatcher->dispatch(UserEvents::REGISTRATION, $event);

        return $user;
    }

    public function save(User $user)
    {
        $event = new AvatarEvent($user);
        $this->dispatcher->dispatch(AvatarEvents::CHANGE, $event);
        $event = new UserEvent($user);
        $this->dispatcher->dispatch(UserEvents::PROFILE_UPDATE, $event);

        if (!$this->entityManager->contains($user)) {
            $this->entityManager->persist($user);
            $addressIsChanged = true;
        } else {
            $uow = $this->entityManager->getUnitOfWork();
            $metadata = $this->entityManager->getClassMetadata(User::class);
            $uow->recomputeSingleEntityChangeSet($metadata, $user);
            $changeSet = $uow->getEntityChangeSet($user);
            $addressIsChanged = isset($changeSet['address1'])
                || isset($changeSet['city'])
                || isset($changeSet['state'])
                || isset($changeSet['country']);
        }
        $this->entityManager->flush();

        if ($addressIsChanged) {
            $this->updateDistrictsIds($user);
            $this->dispatcher->dispatch(UserEvents::ADDRESS_CHANGE, $event);
        }

        return $user;
    }
}
