<?php
namespace Civix\CoreBundle\Tests\Service;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\Component\Notification\Sender;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Poll\Comment;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Repository\UserRepository;
use Civix\CoreBundle\Service\Poll\QuestionUserPush;
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
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Internal\Hydration\IterableResult;
use Doctrine\ORM\Internal\Hydration\ObjectHydrator;
use Psr\Log\LoggerInterface;

class PushSenderTest extends WebTestCase
{
    public function testSendSharedPostPush()
    {
        $user = new User();
        $user->setFirstName('John')
            ->setLastName('Doe')
            ->setAvatarFileName(uniqid('', true));
        $author = new User();
        $author->setFirstName('Jane');
        $post = new Post();
        $post->setBody(str_repeat('x', 500))
            ->setGroup(new Group())
            ->setUser($author);
        $recipients = [new User(), new User()];
        $userRepository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUsersByGroupAndFollowingForPush'])
            ->getMock();
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->exactly(2))
            ->method('find')
            ->withConsecutive([User::class, 7], [Post::class, 16])
            ->willReturnOnConsecutiveCalls($user, $post);
        /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectHydrator $hydrator */
        $hydrator = $this->getMockBuilder(ObjectHydrator::class)
            ->disableOriginalConstructor()
            ->setMethods(['hydrateRow'])
            ->getMock();
        $hydrator->expects($this->exactly(3))
            ->method('hydrateRow')
            ->with()
            ->willReturnOnConsecutiveCalls(
                ...array_map(function ($recipient) {
                    return [$recipient];
                }, $recipients)
            );
        $em->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($userRepository);
        $userRepository->expects($this->once())
            ->method('getUsersByGroupAndFollowingForPush')
            ->with($post->getGroup(), $user)
            ->willReturn(new IterableResult($hydrator));
        $questionUserPush = new QuestionUserPush($em);
        $sender = $this->getMockBuilder(Sender::class)
            ->disableOriginalConstructor()
            ->getMock();
        $logger = $this->createMock(LoggerInterface::class);
        /** @var \PHPUnit_Framework_MockObject_MockObject|PushSender $pushSender */
        $pushSender = $this->getMockBuilder(PushSender::class)
            ->setConstructorArgs([
                $em,
                $questionUserPush,
                $sender,
                $logger
            ])
            ->setMethods(['send'])
            ->getMock();
        $pushSender->expects($this->exactly(2))
            ->method('send')
            ->withConsecutive(
                ...array_map(function ($recipient) use ($user) {
                    return [
                        $recipient,
                        $user->getFullName(),
                        "shared Jane's post with you: ".str_repeat('x', 300).'...',
                        PushSender::TYPE_PUSH_POST_SHARED,
                        [
                            'target' => [
                                'id' => 16,
                                'type' => 'post-shared',
                            ]
                        ],
                        $user->getAvatarFileName()];
                }, $recipients)
            );
        $pushSender->sendSharedPostPush(7, 16);
    }

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
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        /** @var Connection $conn */
        $conn = $this->getContainer()->get('doctrine')->getConnection();
        /** @var \Civix\CoreBundle\Entity\UserPetition\Comment $comment */
        $comment = $repository->getReference('petition_comment_3');
        $manager->noticeUserPetitionCommented($comment);
        $em->flush();
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
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        /** @var Connection $conn */
        $conn = $this->getContainer()->get('doctrine')->getConnection();
        /** @var \Civix\CoreBundle\Entity\Post\Comment $comment */
        $comment = $repository->getReference('post_comment_3');
        $manager->noticePostCommented($comment);
        $em->flush();
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
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        /** @var Connection $conn */
        $conn = $this->getContainer()->get('doctrine')->getConnection();
        /** @var Comment $comment */
        $comment = $repository->getReference('question_comment_4');
        $manager->noticePollCommented($comment);
        $em->flush();
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