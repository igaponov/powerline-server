<?php

namespace Civix\CoreBundle\Service;

use Civix\CoreBundle\Entity\Activities\Post as ActivityPost;
use Civix\CoreBundle\Entity\Activities\UserPetition as ActivityUserPetition;
use Civix\CoreBundle\Entity\Activity;
use Civix\CoreBundle\Entity\ActivityCondition;
use Civix\CoreBundle\Entity\ActivityRead;
use Civix\CoreBundle\Entity\GroupSection;
use Civix\CoreBundle\Entity\Poll\CommentRate;
use Civix\CoreBundle\Entity\Poll\EducationalContext;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\Poll\Question\LeaderEvent;
use Civix\CoreBundle\Entity\Poll\Question\LeaderNews;
use Civix\CoreBundle\Entity\Poll\Question\PaymentRequest;
use Civix\CoreBundle\Entity\Poll\Question\Petition;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\SocialActivity;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserInterface;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Model\Group\GroupSectionInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ActivityUpdate
{
    /**
     * @var EntityManager
     */
    protected $entityManager;
    /**
     * @var ValidatorInterface
     */
    protected $validator;

    public function __construct(
        EntityManager $entityManager,
        ValidatorInterface $validator
    )
    {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }

    public function publishQuestionToActivity(Question $question)
    {
        $activity = $this->entityManager->getRepository(Activity::class)
            ->findOneBy(['question' => $question]);
        if (!$activity) {
            $class = Activity::getActivityClassByEntity($question);
            /** @var \Civix\CoreBundle\Entity\Activities\Question $activity */
            $activity = new $class;
            $activity->setQuestion($question);
            $activity->setUser($question->getUser());
            $userMethod = 'set'.ucfirst($question->getOwner()->getType());
            $activity->$userMethod($question->getOwner());
        }
        if ($question instanceof LeaderEvent || $question instanceof PaymentRequest) {
            $activity->setTitle($question->getTitle());
        } elseif ($question instanceof Petition) {
            $activity->setTitle($question->getPetitionTitle());
        } else {
            $activity->setTitle('');
        }
        if ($question instanceof LeaderNews) {
            $activity->setDescription(strip_tags($question->getSubjectParsed()));
        } elseif ($question instanceof Petition) {
            $activity->setDescription($question->getPetitionBody());
        } else {
            $activity->setDescription($question->getSubject());
        }
        if ($question instanceof PaymentRequest && $question->getIsCrowdfunding()) {
            $activity->setExpireAt($question->getCrowdfundingDeadline());
        } elseif ($question instanceof LeaderEvent) {
            $activity->setExpireAt($question->getStartedAt());
        } else {
            $activity->setExpireAt($question->getExpireAt());
        }
        $activity->setSentAt($question->getPublishedAt());
        $this->setImage($activity, $question);

        $isNew = !$this->entityManager->contains($activity);
        $this->entityManager->persist($activity);
        $this->entityManager->flush();

        if ($isNew) {
            $this->createActivityConditionsForQuestion($activity, $question);
            $this->createUserActivityConditions($activity, $activity->getUser());
        }

        return $activity;
    }

    public function publishUserPetitionToActivity(UserPetition $petition, $isPublic = false): void
    {
        $activity = $this->entityManager->getRepository(Activity::class)
            ->findOneBy(['petition' => $petition]);
        if (!$activity) {
            $activity = new ActivityUserPetition();
            $activity->setPetition($petition);
            $activity->setSentAt(new \DateTime());
            $activity->setGroup($petition->getGroup());
            if (!$isPublic) {
                $activity->setUser($petition->getUser()); // set user as owner
            }
        }
        $activity->setTitle($petition->getTitle());
        $activity->setDescription($petition->getBody());
        $activity->setIsOutsiders($petition->isOutsidersSign());
        $activity->setQuorum($petition->getQuorumCount());

        $isNew = !$this->entityManager->contains($activity);
        $this->entityManager->persist($activity);
        $this->entityManager->flush();

        if ($isNew) {
            $this->createGroupActivityConditions($activity);
            $this->createUserActivityConditions($activity, $activity->getUser());
        }
    }

    public function publishPostToActivity(Post $post, $isPublic = false): void
    {
        $activity = $this->entityManager->getRepository(Activity::class)
            ->findOneBy(['post' => $post]);
        if (!$activity) {
            $activity = new ActivityPost();
            $activity->setPost($post);
            $activity->setSentAt(new \DateTime());
            $activity->setGroup($post->getGroup());
            if (!$isPublic) {
                $activity->setUser($post->getUser()); // set user as owner
            }
        }
        $activity->setTitle('');
        $activity->setDescription($post->getBody());
        $activity->setQuorum($post->getQuorumCount());
        $activity->setExpireAt($post->getExpiredAt());

        $isNew = !$this->entityManager->contains($activity);
        $this->entityManager->persist($activity);
        $this->entityManager->flush();

        if ($isNew) {
            $this->createGroupActivityConditions($activity);
            $this->createUserActivityConditions($activity, $activity->getUser());
        }
    }

    public function updateResponsesQuestion(Question $question): void
    {
        $this->entityManager->getRepository('CivixCoreBundle:Poll\Question')->updateAnswersCount($question);
        $this->entityManager->getRepository('CivixCoreBundle:Activity')->updateResponseCountQuestion($question);
    }

    public function updateResponsesPetition(UserPetition $petition): void
    {
        $this->entityManager->getRepository('CivixCoreBundle:Activity')->updateResponseCountUserPetition($petition);
    }

    public function updateResponsesPost(Post $post): void
    {
        $this->entityManager->getRepository('CivixCoreBundle:Activity')->updateResponseCountPost($post);
    }

    public function updatePetitionAuthorActivity(UserPetition $petition, User $answerer): void
    {
        if ($petition->getUser()->getIsNotifOwnPostChanged() && $petition->getSubscribers()->contains($petition->getUser())) {
            $socialActivity = new SocialActivity(
                SocialActivity::TYPE_OWN_USER_PETITION_SIGNED,
                null,
                $petition->getGroup()
            );
            $socialActivity->setTarget([
                'id' => $petition->getId(),
                'type' => 'user-petition',
                'user_id' => $answerer->getId(),
                'full_name' => $answerer->getFullName(),
                'image' => $answerer->getAvatarFileName(),
            ]);
            $socialActivity->setRecipient($petition->getUser());
            $this->entityManager->persist($socialActivity);
            $this->entityManager->flush();
        }
    }

    public function updatePostAuthorActivity(Post $post, User $answerer): void
    {
        if ($post->getUser()->getIsNotifOwnPostChanged() && $post->getSubscribers()->contains($post->getUser())) {
            $socialActivity = new SocialActivity(
                SocialActivity::TYPE_OWN_POST_VOTED,
                null,
                $post->getGroup()
            );
            $socialActivity->setTarget([
                'id' => $post->getId(),
                'type' => 'post',
                'user_id' => $answerer->getId(),
                'full_name' => $answerer->getFullName(),
                'image' => $answerer->getAvatarFileName(),
            ]);
            $socialActivity->setRecipient($post->getUser());
            $this->entityManager->persist($socialActivity);
            $this->entityManager->flush();
        }
    }

    public function updateOwnerData(UserInterface $owner): void
    {
        $this->entityManager->getRepository('CivixCoreBundle:Activity')->{'updateOwner'.$owner->getType()}($owner);
    }

    public function updateEntityRateCount(CommentRate $rate): void
    {
        $comment = $rate->getComment();
        $user = $rate->getUser();
        $activities = $this->entityManager->getRepository(Activity::class)
            ->findByQuestionWithUserReadMark($comment->getQuestion(), $user);

        /* @var Activity $activity */
        foreach ($activities as $activity) {
            $activity
                ->setRateUp($comment->getRateUp())
                ->setRateDown($comment->getRateDown());
            if (!$activity->isReadByUser($user)) {
                $activityRead = new ActivityRead();
                $activityRead->setUser($user);
                $activityRead->setActivity($activity);
                $this->entityManager->persist($activityRead);
            }
            $this->entityManager->persist($activity);
        }
        $this->entityManager->flush();
    }

    private function setImage(Activity $activity, Question $question)
    {
        /* @var EducationalContext $ec */
        foreach ($question->getEducationalContext() as $ec) {
            if ($ec->hasPreviewImage()) {
                return $activity->setImageSrc($ec->getPreviewSrc());
            }
        }

        return $activity;
    }

    private function createActivityConditionsForQuestion(Activity $activity, Question $question): void
    {
        if ($activity->getRepresentative() && $activity->getRepresentative()->getDistrict()) {
            $this->createRepresentativeActivityConditions($activity);
        } elseif ($activity->getGroup()) {
            if (($question instanceof GroupSectionInterface) && $question->getGroupSections()->count() > 0) {
                foreach ($question->getGroupSections() as $section) {
                    $this->createGroupSectionActivityConditions($activity, $section);
                }
            } else {
                $this->createGroupActivityConditions($activity);
            }
        }
    }

    private function createRepresentativeActivityConditions(Activity $activity): void
    {
        $condition = new ActivityCondition($activity);
        $condition->setDistrict($activity->getRepresentative()->getDistrict());
        $this->entityManager->persist($condition);
        $this->entityManager->flush($condition);
    }

    private function createGroupActivityConditions(Activity $activity): void
    {
        $condition = new ActivityCondition($activity);
        $condition->setGroup($activity->getGroup());
        $this->entityManager->persist($condition);
        $this->entityManager->flush($condition);
    }

    private function createGroupSectionActivityConditions(Activity $activity, GroupSection $section): void
    {
        $condition = new ActivityCondition($activity);
        $condition->setGroupSection($section);
        $this->entityManager->persist($condition);
        $this->entityManager->flush($condition);
    }

    private function createUserActivityConditions(Activity $activity, User $user): void
    {
        $condition = new ActivityCondition($activity);
        $condition->setUser($user);
        $this->entityManager->persist($condition);
        $this->entityManager->flush($condition);
    }
}
