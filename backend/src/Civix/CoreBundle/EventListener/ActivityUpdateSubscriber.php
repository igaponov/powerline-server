<?php
namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\Poll\Comment;
use Civix\CoreBundle\Entity\Poll\CommentRate;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\Poll\Question\LeaderNews;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Event;
use Civix\CoreBundle\Service\ActivityUpdate;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ActivityUpdateSubscriber implements EventSubscriberInterface
{
    /**
     * @var ActivityUpdate
     */
    private $activityUpdate;

    public function __construct(ActivityUpdate $activityUpdate)
    {
        $this->activityUpdate = $activityUpdate;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Event\UserEvents::PROFILE_UPDATE => 'updateOwnerData',

            Event\UserPetitionEvents::PETITION_POST_CREATE => ['publishUserPetitionToActivity', -100],
            Event\UserPetitionEvents::PETITION_UPDATE => ['publishUserPetitionToActivity', -100],
            Event\UserPetitionEvents::PETITION_SIGN => [
                ['updateResponsesPetition', -110],
                ['updatePetitionAuthorActivity', -100],
            ],
            Event\UserPetitionEvents::PETITION_UNSIGN => ['updatePetitionAuthorActivity', -100],
            Event\UserPetitionEvents::PETITION_BOOST => ['publishUserPetitionToActivity', -100],

            Event\PostEvents::POST_POST_CREATE => ['publishPostToActivity', -100],
            Event\PostEvents::POST_UPDATE => ['publishPostToActivity', -100],
            Event\PostEvents::POST_VOTE => [
                ['updateResponsesPost', -110],
                ['updatePostAuthorActivity', -100],
            ],
            Event\PostEvents::POST_UNVOTE => ['updatePostAuthorActivity', -100],
            Event\PostEvents::POST_BOOST => ['publishPostToActivity', -100],

            Event\PollEvents::QUESTION_PUBLISHED => ['publishQuestionToActivity', -100],
            Event\PollEvents::QUESTION_ANSWER => ['updateResponsesQuestion', -110],

            Event\CommentEvents::RATE => ['updateEntityRateCount', -100],
            Event\CommentEvents::CREATE => ['updateResponsesComment', -110],
        ];
    }

    public function publishUserPetitionToActivity(Event\UserPetitionEvent $event)
    {
        $this->activityUpdate->publishUserPetitionToActivity($event->getPetition());
    }

    public function publishPostToActivity(Event\PostEvent $event)
    {
        $this->activityUpdate->publishPostToActivity($event->getPost());
    }

    public function updateResponsesPetition(Event\UserPetition\SignatureEvent $event)
    {
        $this->activityUpdate->updateResponsesPetition($event->getSignature()->getPetition());
    }

    public function updateResponsesPost(Event\Post\VoteEvent $event)
    {
        $this->activityUpdate->updateResponsesPost($event->getVote()->getPost());
    }

    public function updateResponsesQuestion(Event\Poll\AnswerEvent $event)
    {
        $this->activityUpdate->updateResponsesQuestion($event->getAnswer()->getQuestion());
    }

    public function updateResponsesComment(Event\CommentEvent $event)
    {
        $comment = $event->getComment();

        $entity = $comment->getCommentedEntity();
        if ($entity instanceof Post) {
            $this->activityUpdate->updateResponsesPost($entity);
        } elseif ($entity instanceof UserPetition) {
            $this->activityUpdate->updateResponsesPetition($entity);
        } elseif ($entity instanceof Question) {
            $this->activityUpdate->updateResponsesQuestion($entity);
        }
    }

    public function updatePetitionAuthorActivity(Event\UserPetition\SignatureEvent $event)
    {
        $answer = $event->getSignature();
        $this->activityUpdate->updatePetitionAuthorActivity($answer->getPetition(), $answer->getUser());
    }

    public function updatePostAuthorActivity(Event\Post\VoteEvent $event)
    {
        $answer = $event->getVote();
        $this->activityUpdate->updatePostAuthorActivity($answer->getPost(), $answer->getUser());
    }

    public function publishQuestionToActivity(Event\Poll\QuestionEvent $event)
    {
        $question = $event->getQuestion();
        $this->activityUpdate->publishQuestionToActivity($question);
    }

    public function updateEntityRateCount(Event\RateEvent $event)
    {
        $rate = $event->getRate();
        $comment = $rate->getComment();
        if ($rate instanceof CommentRate &&
            $comment instanceof Comment &&
            !$comment->getParentComment() &&
            $comment->getQuestion() instanceof LeaderNews
        ) {
            $this->activityUpdate->updateEntityRateCount($rate);
        }
    }

    public function updateOwnerData(Event\UserEvent $event)
    {
        $this->activityUpdate->updateOwnerData($event->getUser());
    }
}