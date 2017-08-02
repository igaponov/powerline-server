<?php
namespace Civix\CoreBundle\Tests\Service;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Poll\Comment;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Service\PushSender;
use Civix\CoreBundle\Service\SocialActivityManager;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadAnnouncementGroupSectionData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadGroupQuestionData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadQuestionCommentData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadQuestionGroupSectionData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupAnnouncementData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupSectionUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPollSubscriberData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostCommentData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostSubscriberData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserGroupOwnerData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserPetitionCommentData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserPetitionSubscriberData;
use Doctrine\DBAL\Connection;

class PushSenderTest extends WebTestCase
{
    public function testSendPushPublishQuestionToGroupUsers()
    {
        $repository = $this->loadFixtures([
            LoadGroupQuestionData::class,
            LoadUserGroupOwnerData::class,
        ])->getReferenceRepository();
        /** @var Question $poll */
        $poll = $repository->getReference('group_question_1');
        $user = $repository->getReference('user_1');
        $sender = $this->getPushSenderMock();
        $sender->expects($this->once())
            ->method('send')
            ->with(
                $user,
                'Test title',
                'test msg',
                $poll->getType(),
                [
                    'target' => [
                        'id' => $poll->getId(),
                        'type' => 'poll-published',
                    ],
                ],
                $poll->getGroup()->getAvatarFileName()
            );
        $sender->sendPushPublishQuestion($poll->getId(), 'Test title', 'test msg');
    }

    public function testSendPushPublishQuestionToGroupSectionUsers()
    {
        $repository = $this->loadFixtures([
            LoadGroupQuestionData::class,
            LoadGroupSectionUserData::class,
            LoadQuestionGroupSectionData::class,
        ])->getReferenceRepository();
        /** @var Question $poll */
        $poll = $repository->getReference('group_question_3');
        $user = $repository->getReference('user_1');
        $sender = $this->getPushSenderMock();
        $sender->expects($this->once())
            ->method('send')
            ->with(
                $user,
                'Test title',
                'test msg',
                $poll->getType(),
                [
                    'target' => [
                        'id' => $poll->getId(),
                        'type' => 'poll-published',
                    ],
                ],
                $poll->getGroup()->getAvatarFileName()
            )
        ;
        $sender->sendPushPublishQuestion($poll->getId(), 'Test title', 'test msg');
    }

    public function testSendPublishedGroupAnnouncementPushToGroupUsers()
    {
        $repository = $this->loadFixtures([
            LoadGroupAnnouncementData::class,
            LoadUserGroupOwnerData::class,
        ])->getReferenceRepository();
        /** @var Group $group */
        $group = $repository->getReference('group_1');
        $announcement = $repository->getReference('announcement_group_1');
        $user = $repository->getReference('user_1');
        $sender = $this->getPushSenderMock();
        $sender->expects($this->once())
            ->method('send')
            ->with(
                $user,
                $group->getOfficialName(),
                $announcement->getContent(),
                PushSender::TYPE_PUSH_ANNOUNCEMENT,
                [
                    'target' => [
                        'id' => $announcement->getId(),
                        'type' => 'announcement-published',
                    ],
                ],
                $group->getAvatarFileName()
            );
        $sender->sendPublishedGroupAnnouncementPush($group->getId(), $announcement->getId());
    }

    public function testSendPublishedGroupAnnouncementPushToGroupSectionUsers()
    {
        $repository = $this->loadFixtures([
            LoadGroupAnnouncementData::class,
            LoadGroupSectionUserData::class,
            LoadAnnouncementGroupSectionData::class,
        ])->getReferenceRepository();
        /** @var Group $group */
        $group = $repository->getReference('group_1');
        $announcement = $repository->getReference('announcement_group_3');
        $user = $repository->getReference('user_1');
        $sender = $this->getPushSenderMock();
        $sender->expects($this->once())
            ->method('send')
            ->with(
                $user,
                $group->getOfficialName(),
                $announcement->getContent(),
                PushSender::TYPE_PUSH_ANNOUNCEMENT,
                [
                    'target' => [
                        'id' => $announcement->getId(),
                        'type' => 'announcement-published',
                    ],
                ],
                $group->getAvatarFileName()
            )
        ;
        $sender->sendPublishedGroupAnnouncementPush($group->getId(), $announcement->getId());
    }

    public function testSendSocialActivityOnPetitionComment()
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionCommentData::class,
            LoadUserPetitionSubscriberData::class,
        ])->getReferenceRepository();
        /** @var SocialActivityManager $manager */
        $manager = $this->getContainer()->get('civix_core.social_activity_manager');
        /** @var Connection $conn */
        $conn = $this->getContainer()->get('doctrine')->getConnection();
        /** @var \Civix\CoreBundle\Entity\UserPetition\Comment $comment */
        $comment = $repository->getReference('petition_comment_3');
        $manager->noticeUserPetitionCommented($comment);
        $id = $conn->fetchColumn('SELECT id FROM social_activities');
        $sender = $this->getPushSenderMock();
        $sender->expects($this->once())->method('send');
        $sender->sendSocialActivity($id);
    }

    public function testSendSocialActivityOnPostComment()
    {
        $repository = $this->loadFixtures([
            LoadPostCommentData::class,
            LoadPostSubscriberData::class,
        ])->getReferenceRepository();
        /** @var SocialActivityManager $manager */
        $manager = $this->getContainer()->get('civix_core.social_activity_manager');
        /** @var Connection $conn */
        $conn = $this->getContainer()->get('doctrine')->getConnection();
        /** @var \Civix\CoreBundle\Entity\Post\Comment $comment */
        $comment = $repository->getReference('post_comment_3');
        $manager->noticePostCommented($comment);
        $id = $conn->fetchColumn('SELECT id FROM social_activities');
        $sender = $this->getPushSenderMock();
        $sender->expects($this->once())->method('send');
        $sender->sendSocialActivity($id);
    }

    public function testSendSocialActivityOnPollComment()
    {
        $repository = $this->loadFixtures([
            LoadQuestionCommentData::class,
            LoadPollSubscriberData::class,
        ])->getReferenceRepository();
        /** @var SocialActivityManager $manager */
        $manager = $this->getContainer()->get('civix_core.social_activity_manager');
        /** @var Connection $conn */
        $conn = $this->getContainer()->get('doctrine')->getConnection();
        /** @var Comment $comment */
        $comment = $repository->getReference('question_comment_4');
        $manager->noticePollCommented($comment);
        $id = $conn->fetchColumn('SELECT id FROM social_activities');
        $sender = $this->getPushSenderMock();
        $sender->expects($this->exactly(2))->method('send');
        $sender->sendSocialActivity($id);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PushSender
     */
    private function getPushSenderMock()
    {
        $sender = $this->getMockBuilder(PushSender::class)
            ->setConstructorArgs(
                [
                    $this->getContainer()
                        ->get('doctrine')->getManager(),
                    $this->getContainer()
                        ->get('civix_core.question_users_push'),
                    $this->getContainer()
                        ->get('civix.notification.sender'),
                    $this->getContainer()
                        ->get('logger'),
                    $this->getContainer()
                        ->get('imgix.url_builder'),
                    'powerli.ne',
                ]
            )
            ->setMethods(['send'])
            ->getMock();

        return $sender;
    }
}