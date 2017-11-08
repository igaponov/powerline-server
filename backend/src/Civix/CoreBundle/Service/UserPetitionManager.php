<?php

namespace Civix\CoreBundle\Service;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Entity\UserPetition\Signature;
use Civix\CoreBundle\Event\UserPetition\SignatureEvent;
use Civix\CoreBundle\Event\UserPetitionEvent;
use Civix\CoreBundle\Event\UserPetitionEvents;
use Civix\CoreBundle\Event\UserPetitionShareEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class UserPetitionManager
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $dispatcher
    )
    {
        $this->entityManager = $entityManager;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Sign a petition with an answer
     *
     * @param UserPetition $petition
     * @param User $user
     *
     * @return Signature
     */
    public function signPetition(UserPetition $petition, User $user): Signature
    {
        $signature = $petition->sign($user);
        $this->entityManager->persist($petition);
        $this->entityManager->flush();

        $signatureEvent = new SignatureEvent($signature);
        $this->dispatcher->dispatch(UserPetitionEvents::PETITION_SIGN, $signatureEvent);

        if (!$petition->isBoosted()
            && $this->checkIfNeedBoost($petition)
            && $petition->isAutomaticBoost()
        ) {
            $this->boostPetition($petition);
        }

        return $signature;
    }

    /**
     * Unsign a petition with an answer
     *
     * @param Signature $signature
     * @return Signature
     */
    public function unsignPetition(Signature $signature): Signature
    {
        $this->entityManager->remove($signature);
        $this->entityManager->flush();

        $event = new SignatureEvent($signature);
        $this->dispatcher->dispatch(UserPetitionEvents::PETITION_UNSIGN, $event);

        return $signature;
    }

    /**
     * @param UserPetition $petition
     */
    public function boostPetition(UserPetition $petition): void
    {
        $petition->boost();
        $this->entityManager->persist($petition);
        $this->entityManager->flush();

        $petitionEvent = new UserPetitionEvent($petition);
        $this->dispatcher->dispatch(UserPetitionEvents::PETITION_BOOST, $petitionEvent);
    }

    /**
     * One user can create only 5 micropetition in one group per month.
     * 
     * @param \Civix\CoreBundle\Entity\User  $user
     * @param \Civix\CoreBundle\Entity\Group $petitionGroup
     *
     * @return bool
     */
    public function checkPetitionLimitPerMonth(User $user, Group $petitionGroup): bool
    {
        $currentPetitionCount = $this->entityManager
            ->getRepository(UserPetition::class)
            ->getCountPerMonthPetitionByOwner($user, $petitionGroup);

        return $currentPetitionCount < $petitionGroup->getPetitionPerMonth();
    }

    /**
     * Check count answers from group of petition. If it greater than 10% group's followers
     * than need to publish to activity.
     *  
     * @param UserPetition $petition
     * 
     * @return bool
     */
    public function checkIfNeedBoost(UserPetition $petition): bool
    {
        if ($petition->isOrganizationNeeded()) {
            return false;
        }
        $groupAnswers = $this->entityManager->getRepository(Signature::class)
             ->getCountSignatureFromGroup($petition);

        return $groupAnswers >= $petition->getQuorumCount();
    }

    /**
     * @param UserPetition $petition
     * @return UserPetition
     */
    public function savePetition(UserPetition $petition): UserPetition
    {
        $isNew = !$petition->getId();
        $event = new UserPetitionEvent($petition);
        if ($isNew) {
            $this->dispatcher->dispatch(UserPetitionEvents::PETITION_PRE_CREATE, $event);
            $this->entityManager->persist($petition);
        }

        $this->entityManager->flush();

        if ($isNew) {
            $this->dispatcher->dispatch(UserPetitionEvents::PETITION_POST_CREATE, $event);
        }

        $eventName = $isNew ? UserPetitionEvents::PETITION_CREATE : UserPetitionEvents::PETITION_UPDATE;
        $this->dispatcher->dispatch('async.'.$eventName, $event);

        return $petition;
    }

    public function sharePetition(UserPetition $petition, User $sharer): void
    {
        $filter = function (Signature $signature) use ($sharer) {
            return $signature->getUser()->isEqualTo($sharer);
        };
        if ($petition->getSignatures()->filter($filter)->isEmpty()) {
            throw new \DomainException('User can share only a petition he has signed.');
        }
        if ($sharer->getLastContentSharedAt() > new \DateTime('-1 hour')) {
            throw new \DomainException('User can share a petition only once in 1 hour.');
        }

        $sharer->shareContent();

        $event = new UserPetitionShareEvent($petition, $sharer);
        $this->dispatcher->dispatch(UserPetitionEvents::PETITION_SHARE, $event);
    }
}
