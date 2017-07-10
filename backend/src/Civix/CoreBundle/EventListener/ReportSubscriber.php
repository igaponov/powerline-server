<?php

namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Report\MembershipReport;
use Civix\CoreBundle\Entity\Report\PetitionResponseReport;
use Civix\CoreBundle\Entity\Report\PollResponseReport;
use Civix\CoreBundle\Entity\Report\PostResponseReport;
use Civix\CoreBundle\Entity\Report\UserReport;
use Civix\CoreBundle\Event;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ReportSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public static function getSubscribedEvents(): array
    {
        return [
            Event\GroupEvents::USER_INQUIRED => 'createMembershipReport',
            Event\GroupEvents::USER_JOINED => [
                ['updateUserGroupReport'],
                ['updateKarmaJoinGroup', -100],
            ],
            Event\GroupEvents::USER_UNJOIN => 'deleteMembershipReport',
            Event\PollEvents::QUESTION_ANSWER => [
                ['createPollReport'],
                ['updateKarmaAnswerPoll', -100],
            ],
            Event\PostEvents::POST_VOTE => [
                ['createPostReport'],
                ['updateKarmaReceiveUpvoteOnPost', -100],
            ],
            Event\PostEvents::POST_UNVOTE => [
                ['deletePostReport'],
                ['updateKarmaReceiveUpvoteOnPost', -100],
            ],
            Event\UserEvents::FOLLOW_REQUEST_APPROVE => [
                ['updateUserReport'],
                ['updateKarmaApproveFollowRequest', -100],
            ],
            Event\UserEvents::REGISTRATION => 'createUserReport',
            Event\UserEvents::UNFOLLOW => 'updateUserReport',
            Event\UserPetitionEvents::PETITION_SIGN => 'createPetitionReport',
            Event\UserPetitionEvents::PETITION_UNSIGN => 'deletePetitionReport',

            Event\UserEvents::VIEW_REPRESENTATIVES => ['updateKarmaRepresentativeScreen', -100],
            Event\UserEvents::FOLLOW => ['updateKarmaFollow', -100],
            Event\PostEvents::POST_CREATE => ['updateKarmaCreatePost', -100],
            Event\CommentEvents::RATE => ['updateKarmaReceiveUpvoteOnComment', -100],
            Event\AnnouncementEvents::MARK_AS_READ => ['updateKarmaViewAnnouncement', -100],
        ];
    }

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function updateUserReport(Event\UserFollowEvent $event): void
    {
        $user = $event->getUserFollow()->getUser();
        $this->em->getRepository(UserReport::class)
            ->upsertUserReport($user, $user->getFollowers()->count());
    }

    public function createUserReport(Event\UserEvent $event): void
    {
        $this->em->getRepository(UserReport::class)
            ->upsertUserReport($event->getUser(), 0);
    }

    public function createMembershipReport(Event\InquiryEvent $event): void
    {
        $worksheet = $event->getWorksheet();
        $user = $worksheet->getUser();
        $group = $worksheet->getGroup();
        $answeredFields = $worksheet->getAnsweredFields();
        $fields = [];
        foreach ($answeredFields as $field) {
            $fields[$field->getId()] = $field->getValue();
        }
        $this->em->getRepository(MembershipReport::class)
            ->upsertMembershipReport($user, $group, $fields);
    }

    public function deleteMembershipReport(Event\GroupUserEvent $event): void
    {
        $this->em->getRepository(MembershipReport::class)
            ->deleteMembershipReport($event->getUser(), $event->getGroup());
    }

    public function createPollReport(Event\Poll\AnswerEvent $event): void
    {
        $this->em->getRepository(PollResponseReport::class)
            ->insertPollResponseReport($event->getAnswer());
    }

    public function updateUserGroupReport(Event\GroupUserEvent $event): void
    {
        $group = $event->getGroup();
        $user = $event->getUser();

        $country = $state = $locality = null;
        switch ($group->getGroupType()) {
            case Group::GROUP_TYPE_COUNTRY:
                $country = $group->getOfficialName();
                break;
            case Group::GROUP_TYPE_STATE:
                $state = $group->getOfficialName();
                break;
            case Group::GROUP_TYPE_LOCAL:
                $locality = $group->getOfficialName();
                break;
            default:
                return;
        }

        $this->em->getRepository(UserReport::class)
            ->upsertUserReport($user, null, null, $country, $state, $locality);
    }

    public function createPostReport(Event\Post\VoteEvent $event): void
    {
        $this->em->getRepository(PostResponseReport::class)
            ->upsertPostResponseReport($event->getVote());
    }

    public function deletePostReport(Event\Post\VoteEvent $event): void
    {
        $this->em->getRepository(PostResponseReport::class)
            ->deletePostResponseReport($event->getVote());
    }

    public function createPetitionReport(Event\UserPetition\SignatureEvent $event): void
    {
        $this->em->getRepository(PetitionResponseReport::class)
            ->insertPetitionResponseReport($event->getSignature());
    }

    public function deletePetitionReport(Event\UserPetition\SignatureEvent $event): void
    {
        $this->em->getRepository(PetitionResponseReport::class)
            ->deletePetitionResponseReport($event->getSignature());
    }

    public function updateKarmaRepresentativeScreen(Event\UserRepresentativeEvent $event): void
    {
        $this->em->getRepository(UserReport::class)
            ->updateUserReportKarma($event->getUser());
    }

    public function updateKarmaFollow(Event\UserFollowEvent $event): void
    {
        $this->em->getRepository(UserReport::class)
            ->updateUserReportKarma(
                $event->getUserFollow()
                    ->getFollower()
            );
    }

    public function updateKarmaApproveFollowRequest(Event\UserFollowEvent $event): void
    {
        $this->em->getRepository(UserReport::class)
            ->updateUserReportKarma(
                $event->getUserFollow()
                    ->getUser()
            );
    }

    public function updateKarmaJoinGroup(Event\GroupUserEvent $event): void
    {
        $this->em->getRepository(UserReport::class)
            ->updateUserReportKarma($event->getUser());
    }

    public function updateKarmaCreatePost(Event\PostEvent $event): void
    {
        $this->em->getRepository(UserReport::class)
            ->updateUserReportKarma(
                $event->getPost()
                    ->getUser()
            );
    }

    public function updateKarmaAnswerPoll(Event\Poll\AnswerEvent $event): void
    {
        $this->em->getRepository(UserReport::class)
            ->updateUserReportKarma(
                $event->getAnswer()
                    ->getUser()
            );
    }

    public function updateKarmaReceiveUpvoteOnPost(Event\Post\VoteEvent $event): void
    {
        $this->em->getRepository(UserReport::class)
            ->updateUserReportKarma(
                $event->getVote()
                    ->getPost()
                    ->getUser()
            );
    }

    public function updateKarmaReceiveUpvoteOnComment(Event\RateEvent $event): void
    {
        $this->em->getRepository(UserReport::class)
            ->updateUserReportKarma(
                $event->getRate()
                    ->getComment()
                    ->getUser()
            );
    }

    public function updateKarmaViewAnnouncement(Event\UserAnnouncementsEvent $event): void
    {
        $this->em->getRepository(UserReport::class)
            ->updateUserReportKarma($event->getUser());
    }
}