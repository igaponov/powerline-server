<?php

namespace Civix\CoreBundle\Service;

use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\UserInterface;
use Civix\CoreBundle\Entity\SocialActivity;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Model\Group\GroupSectionInterface;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\Poll\Comment;
use Civix\CoreBundle\Entity\Poll\Question\Petition;
use Civix\CoreBundle\Entity\Poll\Question\LeaderNews;
use Civix\CoreBundle\Entity\Poll\Question\PaymentRequest;
use Civix\CoreBundle\Entity\Poll\Question\LeaderEvent;
use Civix\CoreBundle\Entity\Poll\EducationalContext;
use Civix\CoreBundle\Entity\Activities\Question as ActivityQuestion;
use Civix\CoreBundle\Entity\Activities\UserPetition as ActivityUserPetition;
use Civix\CoreBundle\Entity\Activities\Petition as ActivityPetition;
use Civix\CoreBundle\Entity\Activities\LeaderNews as ActivityLeaderNews;
use Civix\CoreBundle\Entity\Activities\LeaderEvent as ActivityLeaderEvent;
use Civix\CoreBundle\Entity\Activities\PaymentRequest as ActivityPaymentRequest;
use Civix\CoreBundle\Entity\Activities\CrowdfundingPaymentRequest as ActivityCrowdfundingPaymentRequest;
use Civix\CoreBundle\Entity\Activities\Post as ActivityPost;
use Civix\CoreBundle\Entity\Activity;
use Civix\CoreBundle\Entity\ActivityCondition;
use Civix\CoreBundle\Entity\GroupSection;
use Civix\CoreBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\ValidatorInterface;

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
    /**
     * @var Settings
     */
    private $settings;
    /**
     * @var Poll\CommentManager
     */
    private $cm;

    public function __construct(
        EntityManager $entityManager,
        ValidatorInterface $validator,
        Settings $settings,
        Poll\CommentManager $cm
    )
    {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->settings = $settings;
        $this->cm = $cm;
    }

    public function publishQuestionToActivity(Question $question)
    {
        //create activity
        $activity = new ActivityQuestion();
        $activity->setQuestionId($question->getId());
        $activity->setTitle('');
        $activity->setDescription($question->getSubject());
        $activity->setSentAt($question->getPublishedAt());
        $activity->setExpireAt($question->getExpireAt());
        $userMethod = 'set'.ucfirst($question->getUser()->getType());
        $activity->$userMethod($question->getUser());
        $this->setImage($activity, $question);

        $this->cm->addPollRootComment($question, $question->getSubject());

        $this->entityManager->persist($activity);
        $this->entityManager->flush();
        $this->createActivityConditionsForQuestion($activity, $question);

        return $activity;
    }

    public function publishUserPetitionToActivity(UserPetition $petition, $isPublic = false)
    {
        $petition->boost();
        $this->entityManager->persist($petition);

        //create activity
        $activity = new ActivityUserPetition();
        $activity->setPetition($petition);
        $activity->setTitle($petition->getTitle());
        $activity->setDescription($petition->getBody());
        $activity->setSentAt(new \DateTime());
        $activity->setResponsesCount($petition->getSignatures()->count());
        $activity->setIsOutsiders($petition->isOutsidersSign());
        $activity->setGroup($petition->getGroup());
        $activity->setQuorum($petition->getQuorumCount());
        if (!$isPublic) {
            $activity->setUser($petition->getUser());
        }

        $this->entityManager->persist($activity);
        $this->entityManager->flush();

        $this->createActivityConditionsForUserPetition($activity, $petition);

        return true;
    }

    public function publishPostToActivity(Post $post, $isPublic = false)
    {
        $post->boost();
        $this->entityManager->persist($post);

        //create activity
        $activity = new ActivityPost();
        $activity->setPost($post);
        $activity->setTitle('');
        $activity->setDescription($post->getBody());
        $activity->setSentAt(new \DateTime());
        $activity->setResponsesCount($post->getVotes()->count());
        $activity->setGroup($post->getGroup());
        $activity->setQuorum($post->getQuorumCount());
        if (!$isPublic) {
            $activity->setUser($post->getUser());
        }

        $this->entityManager->persist($activity);
        $this->entityManager->flush();

        $this->createActivityConditionsForPost($activity, $post);

        return true;
    }

    public function publishLeaderNewsToActivity(LeaderNews $news)
    {
        $expireDate = new \DateTime('now');
        $expireDate->add(
            new \DateInterval('P'.$this->settings->get(Settings::DEFAULT_EXPIRE_INTERVAL)->getValue().'D')
        );

        $activity = new ActivityLeaderNews();
        $activity->setQuestionId($news->getId());
        $activity->setTitle('');
        $activity->setDescription(strip_tags($news->getSubjectParsed()));
        $activity->setSentAt($news->getPublishedAt());
        $activity->setExpireAt($expireDate);
        $method = 'set'.ucfirst($news->getUser()->getType());
        $activity->$method($news->getUser());
        $this->setImage($activity, $news);

        $this->cm->addPollRootComment($news, $news->getSubject());

        $this->entityManager->persist($activity);
        $this->entityManager->flush();
        $this->createActivityConditionsForQuestion($activity, $news);

        return $activity;
    }

    public function publishPetitionToActivity(Petition $petition)
    {
        $expireDate = new \DateTime('now');
        $expireDate->add(
            new \DateInterval('P'.$this->settings->get(Settings::DEFAULT_EXPIRE_INTERVAL)->getValue().'D')
        );
        $activity = new ActivityPetition();
        $activity->setQuestionId($petition->getId())
            ->setTitle($petition->getPetitionTitle())
            ->setDescription($petition->getPetitionBody())
            ->setExpireAt($expireDate)
            ->setSentAt($petition->getPublishedAt());

        $userMethod = 'set'.ucfirst($petition->getUser()->getType());
        $activity->$userMethod($petition->getUser());
        $this->setImage($activity, $petition);

        $this->cm->addPollRootComment($petition, $petition->getPetitionBody());

        $this->entityManager->persist($activity);
        $this->entityManager->flush();
        $this->createActivityConditionsForQuestion($activity, $petition);
    }

    public function publishPaymentRequestToActivity(PaymentRequest $paymentRequest, $users = null)
    {
        if ($paymentRequest->getIsCrowdfunding()) {
            $activity = new ActivityCrowdfundingPaymentRequest();
            $activity->setExpireAt($paymentRequest->getCrowdfundingDeadline());
        } else {
            $activity = new ActivityPaymentRequest();
            $expireDate = new \DateTime('now');
            $expireDate->add(
                new \DateInterval('P'.$this->settings->get(Settings::DEFAULT_EXPIRE_INTERVAL)->getValue().'D')
            );
            $activity->setExpireAt($expireDate);
        }

        $activity
            ->setQuestionId($paymentRequest->getId())
            ->setTitle($paymentRequest->getTitle())
            ->setDescription($paymentRequest->getSubject())
            ->setSentAt($paymentRequest->getPublishedAt())
        ;
        $method = 'set'.ucfirst($paymentRequest->getUser()->getType());
        $activity->$method($paymentRequest->getUser());
        $this->setImage($activity, $paymentRequest);

        $this->cm->addPollRootComment($paymentRequest, $paymentRequest->getTitle());

        $this->entityManager->persist($activity);
        $this->entityManager->flush($activity);
        if ($users) {
            $this->createActivityConditionsForUsers($activity, $users);
        } else {
            $this->createActivityConditionsForQuestion($activity, $paymentRequest);
        }

        return $activity;
    }

    public function publishLeaderEventToActivity(LeaderEvent $event)
    {
        $publishedAt = new \DateTime();
        //update event       
        $event->setPublishedAt($publishedAt);
        $this->entityManager->persist($event);

         //create activity
        $activity = new ActivityLeaderEvent();
        $activity->setQuestionId($event->getId());
        $activity->setTitle($event->getTitle());
        $activity->setDescription($event->getSubject());
        $activity->setSentAt($publishedAt);
        $activity->setExpireAt($event->getStartedAt());
        $userMethod = 'set'.ucfirst($event->getUser()->getType());
        $activity->$userMethod($event->getUser());
        $this->setImage($activity, $event);

        $this->cm->addPollRootComment($event, $event->getSubject());

        $this->entityManager->persist($activity);
        $this->entityManager->flush();
        $this->createActivityConditionsForQuestion($activity, $event);

        return $activity;
    }

    public function updateResponsesQuestion(Question $question)
    {
        if ($question instanceof LeaderNews) {
            $this->entityManager->getRepository('CivixCoreBundle:Activity')
                ->updateLeaderNewsResponseCountQuestion($question);
        } else {
            $this->entityManager->getRepository('CivixCoreBundle:Poll\Question')->updateAnswersCount($question);
            $this->entityManager->getRepository('CivixCoreBundle:Activity')->updateResponseCountQuestion($question);
        }
    }

    public function updateResponsesPetition(UserPetition $petition)
    {
        $this->entityManager->getRepository('CivixCoreBundle:Activity')->updateResponseCountUserPetition($petition);
    }

    public function updateResponsesPost(Post $post)
    {
        $this->entityManager->getRepository('CivixCoreBundle:Activity')->updateResponseCountPost($post);
    }

    public function updatePetitionAuthorActivity(UserPetition $petition, User $answerer)
    {
        if ($petition->getUser()->getIsNotifOwnPostChanged()) {
            $socialActivity = new SocialActivity(
                SocialActivity::TYPE_OWN_POST_VOTED,
                $answerer,
                $petition->getGroup()
            );
            $socialActivity->setTarget([
                'id' => $petition->getId(),
                'type' => 'user-petition',
                'user_id' => $answerer->getId(),
                'first_name' => $answerer->getFirstName(),
                'last_name' => $answerer->getLastName(),
                'image' => $answerer->getAvatarFileName(),
            ]);
            $socialActivity->setRecipient($petition->getUser());
            $this->entityManager->persist($socialActivity);
            $this->entityManager->flush();
        }
    }

    public function updatePostAuthorActivity(Post $post, User $answerer)
    {
        if ($post->getUser()->getIsNotifOwnPostChanged()) {
            $socialActivity = new SocialActivity(
                SocialActivity::TYPE_OWN_POST_VOTED,
                $answerer,
                $post->getGroup()
            );
            $socialActivity->setTarget([
                'id' => $post->getId(),
                'type' => 'post',
                'user_id' => $answerer->getId(),
                'first_name' => $answerer->getFirstName(),
                'last_name' => $answerer->getLastName(),
                'image' => $answerer->getAvatarFileName(),
            ]);
            $socialActivity->setRecipient($post->getUser());
            $this->entityManager->persist($socialActivity);
            $this->entityManager->flush();
        }
    }

    public function updateOwnerData(UserInterface $owner)
    {
        $this->entityManager->getRepository('CivixCoreBundle:Activity')->{'updateOwner'.$owner->getType()}($owner);
    }

    public function updateEntityRateCount(Comment $comment)
    {
        $activities = $this->entityManager->getRepository(Activity::getActivityClassByEntity($comment->getQuestion()))
            ->findBy(['questionId' => $comment->getQuestion()->getId()]);

        /* @var Activity $activity */
        foreach ($activities as $activity) {
            $activity->setRateUp($comment->getRateUp())->setRateDown($comment->getRateDown());
            $this->entityManager->flush($activity);
        }
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

    private function createActivityConditionsForQuestion(Activity $activity, Question $question)
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
        } elseif ($activity->getSuperuser()) {
            $this->createSuperuserActivityConditions($activity);
        }
    }

    /**
     * @param Activity $activity
     * @param array|ArrayCollection $users
     */
    private function createActivityConditionsForUsers(Activity $activity, array $users)
    {
        $condition = new ActivityCondition($activity);
        $condition->setUsers($users);
        $this->entityManager->persist($condition);
        $this->entityManager->flush($condition);
    }

    private function createActivityConditionsForUserPetition(Activity $activity, UserPetition $petition)
    {
        $this->createGroupActivityConditions($activity);
        if ($petition->isOutsidersSign() || !$petition->isBoosted()) {
            $this->createUserActivityConditions($activity, $petition->getUser());
        }
    }

    private function createActivityConditionsForPost(Activity $activity, Post $post)
    {
        $this->createGroupActivityConditions($activity);
        if (!$post->isBoosted()) {
            $this->createUserActivityConditions($activity, $post->getUser());
        }
    }

    private function createRepresentativeActivityConditions(Activity $activity)
    {
        $condition = new ActivityCondition($activity);
        $condition->setDistrictId($activity->getRepresentative()->getDistrictId());
        $this->entityManager->persist($condition);
        $this->entityManager->flush($condition);
    }

    private function createGroupActivityConditions(Activity $activity)
    {
        $condition = new ActivityCondition($activity);
        $condition->setGroupId($activity->getGroup()->getId());
        $this->entityManager->persist($condition);
        $this->entityManager->flush($condition);
    }

    private function createGroupSectionActivityConditions(Activity $activity, GroupSection $section)
    {
        $condition = new ActivityCondition($activity);
        $condition->setGroupSectionId($section->getId());
        $this->entityManager->persist($condition);
        $this->entityManager->flush($condition);
    }

    private function createUserActivityConditions(Activity $activity, User $user)
    {
        $condition = new ActivityCondition($activity);
        $condition->setUserId($user->getId());
        $this->entityManager->persist($condition);
        $this->entityManager->flush($condition);
    }

    private function createSuperuserActivityConditions(Activity $activity)
    {
        $condition = new ActivityCondition($activity);
        $condition->setIsSuperuser(true);
        $this->entityManager->persist($condition);
        $this->entityManager->flush($condition);
    }
}
