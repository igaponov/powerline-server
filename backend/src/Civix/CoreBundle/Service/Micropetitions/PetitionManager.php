<?php

namespace Civix\CoreBundle\Service\Micropetitions;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Micropetitions\Petition;
use Civix\CoreBundle\Entity\Micropetitions\Petition as UserPetition;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserPetition\Signature;
use Civix\CoreBundle\Event\UserPetitionEvents;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PetitionManager
{
    const EXPIRE_INTERVAL = 1;
    const PERCENT_IN_GROUP = 10;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    private $errors;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(
        EntityManager $entityManager, 
        EventDispatcherInterface $dispatcher
    )
    {
        $this->entityManager = $entityManager;
        $this->errors = [];
        $this->dispatcher = $dispatcher;
    }

    public function createPetitionInterval(UserPetition $userPetition, Group $petitionGroup, User $user, $expireInterval = self::EXPIRE_INTERVAL)
    {
        $currentDate = new \DateTime();
        $expireDate = clone $currentDate;
        $expireDate->add(new \DateInterval('P'.$expireInterval.'D'));

        $userPetition->setPublishStatus(UserPetition::STATUS_USER);
        $userPetition->setUser($user);
        $userPetition->setGroup($petitionGroup);
        $userPetition->setCreatedAt($currentDate);
        $userPetition->setExpireAt($expireDate);
        $userPetition->setUserExpireInterval($expireInterval);

        return $userPetition;
    }

    /**
     * @deprecated Use {@link signPetition} instead
     * @param UserPetition $userPetition
     * @param User $user
     * @param $optionId
     * @return bool|Signature
     */
    public function answerToPetition(UserPetition $userPetition, User $user, $optionId)
    {
        $this->errors = [];
        $currentDate = new \DateTime();

        if ($userPetition->getExpireAt() <= $currentDate) {
            $this->errors[] = 'You could not answer to expired micropetition';

            return false;
        }
        if ($userPetition->getUser() == $user) {
            $this->errors[] = 'You could not answer to your micropetition';

            return false;
        }

        if (array_search($optionId, $userPetition->getOptionsIds()) === false) {
            $this->errors[] = 'Incorrect answer\'s option';

            return false;
        }
        $answer = $this->entityManager->getRepository('CivixCoreBundle:Micropetitions\Answer')
            ->findOneBy(array(
                'petition' => $userPetition,
                'user' => $user,
            ));
        if ($answer && $answer->getOptionId() !== 3) {
            $this->errors[] = 'User is already answered this micropetition';

            return false;
        } elseif ($answer) {
            $this->entityManager->remove($answer);
        }

        //add answers
        $answer = $this->entityManager->getRepository('CivixCoreBundle:Micropetitions\Answer')
            ->createAnswer($userPetition, $user, $optionId);

        $this->entityManager->persist($answer);
        $this->entityManager->flush();

        //update response count activity for this petition
        $answerEvent = new AnswerEvent($answer);
        $this->dispatcher->dispatch(UserPetitionEvents::PETITION_SIGN, $answerEvent);

        //check if need to publish to activity
        if ($userPetition->getPublishStatus() == UserPetition::STATUS_USER
            && $this->checkIfNeedPublish($userPetition)
        ) {
            $petitionEvent = new UserPetitionEvent($userPetition);
            $this->dispatcher->dispatch(UserPetitionEvents::PETITION_BOOST, $petitionEvent);
        }

        return $answer;
    }

    /**
     * Sign a petition with an answer
     *
     * @param Signature $answer
     * @return Signature
     */
    public function signPetition(Signature $answer)
    {
        $this->entityManager->persist($answer);
        $this->entityManager->flush();

        //update response count activity for this petition
        $answerEvent = new AnswerEvent($answer);
        $this->dispatcher->dispatch(UserPetitionEvents::PETITION_SIGN, $answerEvent);

        //check if need to publish to activity
        $petition = $answer->getPetition();
        if ($petition->getPublishStatus() == UserPetition::STATUS_USER
            && $this->checkIfNeedPublish($petition)
        ) {
            $petitionEvent = new UserPetitionEvent($petition);
            $this->dispatcher->dispatch(UserPetitionEvents::PETITION_BOOST, $petitionEvent);
        }

        return $answer;
    }

    /**
     * Unsign a petition with an answer
     *
     * @param Signature $answer
     * @return Signature
     */
    public function unsignPetition(Signature $answer)
    {
        $this->entityManager->remove($answer);
        $this->entityManager->flush();

        $event = new AnswerEvent($answer);
        $this->dispatcher->dispatch(UserPetitionEvents::PETITION_UNSIGN, $event);

        return $answer;
    }

    public function recalcVoicesForPetitions(UserPetition $petition)
    {
        $calcValues = $this->entityManager->getRepository('CivixCoreBundle:Micropetitions\Answer')
            ->calcVoices($petition);
        $petition->setCountVoices($calcValues);

        return $petition;
    }

    /**
     * One user can create only 5 micropetition in one group per month.
     * 
     * @param \Civix\CoreBundle\Entity\User  $user
     * @param \Civix\CoreBundle\Entity\Group $petitionGroup
     *
     * @return bool
     */
    public function checkPetitionLimitPerMonth(User $user, Group $petitionGroup)
    {
        $currentPetitionCount = $this->entityManager
            ->getRepository('CivixCoreBundle:Micropetitions\Petition')
            ->getCountPerMonthPetitionByOwner($user, $petitionGroup);

        return $currentPetitionCount < $petitionGroup->getPetitionPerMonth();
    }

    /**
     * Check count answers from group of petition. If it greater than 10% group's followers
     * than need to publish to actitvity.
     *  
     * @param \Civix\CoreBundle\Entity\Micropetitions\Petition $petition
     * 
     * @return bool
     */
    public function checkIfNeedPublish(UserPetition $petition)
    {
        if ($petition->getType() === $petition::TYPE_OPEN_LETTER) {
            return false;
        }
        $groupAnswers = $this->entityManager->getRepository('CivixCoreBundle:Micropetitions\Answer')
             ->getCountAnswerFromGroup($petition);

        return $groupAnswers >= $petition->getQuorumCount();
    }

    public function getStatisticByPetition(UserPetition $petition, $colors)
    {
        $petition = $this->recalcVoicesForPetitions($petition);

        $sum = $petition->getResponsesCount();
        $max = $petition->getMaxAnswers();

        $statistics = array();

        foreach ($petition->getOptions() as $option) {
            /* @var $option  \Civix\CoreBundle\Entity\Poll\Option */
            $stat = array(
                'option' => $option['value'],
                'percent_answer' => $sum > 0 ? round($option['votes_count'] / $sum * 100) : 0,
                'percent_width' => $max > 0 ? round($option['votes_count'] / $max * 100) : 0,
                'color' => current($colors),
            );

            if (1 > $stat['percent_width']) {
                $stat['percent_width'] = 1;
            }

            $statistics[] = $stat;

            next($colors);
        }

        return $statistics;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function savePetition(Petition $petition)
    {
        if ($petition->getType() !== Petition::TYPE_LONG_PETITION) {
            $petition->setTitle(''); //title should be removed in the future
        }

        $event = new UserPetitionEvent($petition);
        $isNew = !$petition->getId();
        if ($isNew) {
            $petition->setPublishStatus(UserPetition::STATUS_USER);
            $this->dispatcher->dispatch(UserPetitionEvents::PETITION_PRE_CREATE, $event);
        }

        $this->entityManager->persist($petition);
        $this->entityManager->flush();

        $this->dispatcher->dispatch($isNew ? UserPetitionEvents::PETITION_CREATE : UserPetitionEvents::PETITION_UPDATE, $event);

        return $petition;
    }
}
