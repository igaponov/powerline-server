<?php

namespace Civix\CoreBundle\Service\Poll;

use Civix\CoreBundle\Entity\Poll\Answer;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\Poll\Question\Petition;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\UserRepresentative;
use Civix\CoreBundle\Entity\Superuser;
use Civix\CoreBundle\Entity\UserGroup;
use Doctrine\ORM\EntityManager;

class AnswerManager
{
    protected $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @deprecated use PollVoter with `ANSWER` attribute instead
     * @param User $user
     * @param Question $question
     * @return bool
     */
    public function checkAccessAnswer(User $user, Question $question)
    {
        if ($question instanceof Petition && $question->getIsOutsidersSign()) {
            return true;
        }

        $questionOwner = $question->getOwner();

        if ($questionOwner instanceof Superuser) {
            return true;
        }

        if ($questionOwner instanceof Group) {
            $userGroup = $this->entityManager->getRepository('CivixCoreBundle:UserGroup')
                ->isJoinedUser($questionOwner, $user);

            if ($userGroup instanceof UserGroup &&
                $userGroup->getStatus() == UserGroup::STATUS_ACTIVE
            ) {
                return true;
            }

            return false;
        }

        if ($questionOwner instanceof UserRepresentative) {
            $userDistricts = $user->getDistrictsIds();

            if (array_search($questionOwner->getDistrictId(), $userDistricts) !== false) {
                return true;
            }

            return false;
        }

        return false;
    }
}
