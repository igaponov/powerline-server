<?php
namespace Civix\CoreBundle\Tests\Service;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Service\PushSender;
use Civix\CoreBundle\Service\SocialActivityManager;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadQuestionCommentData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPollSubscriberData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostCommentData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostSubscriberData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserPetitionCommentData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserPetitionSubscriberData;
use Doctrine\DBAL\Connection;

class PushSenderTest extends WebTestCase
{
    public function testSendSocialActivityOnPetitionComment()
    {
        $repository = $this->loadFixtures([
            LoadUserPetitionCommentData::class,
            LoadUserPetitionSubscriberData::class,
        ])->getReferenceRepository();
        /** @var SocialActivityManager $manager */
        $manager = $this->getContainer()->get('civix_core.social_activity_manager');
        /** @var Connection $conn */
        $conn = $this->getContainer()->get('doctrine.dbal.default_connection');
        $comment = $repository->getReference('petition_comment_3');
        $manager->noticeUserPetitionCommented($comment);
        $id = $conn->fetchColumn('SELECT id FROM social_activities');
        $sender = $this->getPushSenderMock();
        $sender->expects($this->exactly(2))->method('send');
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
        $conn = $this->getContainer()->get('doctrine.dbal.default_connection');
        $comment = $repository->getReference('post_comment_3');
        $manager->noticePostCommented($comment);
        $id = $conn->fetchColumn('SELECT id FROM social_activities');
        $sender = $this->getPushSenderMock();
        $sender->expects($this->exactly(2))->method('send');
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
        $conn = $this->getContainer()->get('doctrine.dbal.default_connection');
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
                        ->get('doctrine.orm.entity_manager'),
                    $this->getContainer()
                        ->get('civix_core.question_users_push'),
                    $this->getContainer()
                        ->get('civix_core.notification'),
                    $this->getContainer()
                        ->get('logger'),
                    $this->getContainer()
                        ->get('imgix.url_builder'),
                ]
            )
            ->setMethods(['send'])
            ->getMock();

        return $sender;
    }
}