<?php

namespace Tests\Civix\CoreBundle\EventListener;

use Civix\ApiBundle\EventListener\LeaderContentSubscriber;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\HashTag;
use Civix\CoreBundle\Entity\HashTaggableInterface;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\Setting;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Event\PostEvent;
use Civix\CoreBundle\Event\UserPetitionEvent;
use Civix\CoreBundle\Repository\HashTagRepository;
use Civix\CoreBundle\Service\CommentManager;
use Civix\CoreBundle\Service\Settings;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class LeaderContentSubscriberTest extends TestCase
{
    public function testSetPostExpire()
    {
        $key = 'micropetition_expire_interval_0';
        $setting = new Setting($key, '13579');
        $group = (new Group())->setGroupType(Group::GROUP_TYPE_COMMON);
        $post = (new Post())->setGroup($group);
        $em = $this->createMock(EntityManagerInterface::class);
        $settings = $this->getSettingsMock(['get']);
        $settings->expects($this->once())
            ->method('get')
            ->with($key)
            ->willReturn($setting);
        $commentManager = $this->getCommentManagerMock();
        $subscriber = new LeaderContentSubscriber($em, $settings, $commentManager);
        $event = new PostEvent($post);
        $subscriber->setPostExpire($event);
        $this->assertSame(13579, $post->getUserExpireInterval());
        $this->assertLessThan(new \DateTime('+13579 days + 1 second'), $post->getExpiredAt());
        $this->assertGreaterThan(new \DateTime('+13579 days - 1 second'), $post->getExpiredAt());
    }

    public function testAddPostHashTags()
    {
        $post = new Post();
        $subscriber = $this->getLeaderContentSubscriberForAddHashTagsTest($post);
        $event = new PostEvent($post);
        $subscriber->addPostHashTags($event);
    }

    public function testAddPetitionHashTags()
    {
        $petition = new UserPetition();
        $subscriber = $this->getLeaderContentSubscriberForAddHashTagsTest($petition);
        $event = new UserPetitionEvent($petition);
        $subscriber->addPetitionHashTags($event);
    }

    public function testSubscribePostAuthor()
    {
        $user = new User();
        $post = new Post();
        $post->setUser($user);
        $subscriber = $this->getLeaderContentSubscriberForSubscribeAuthor();
        $event = new PostEvent($post);
        $subscriber->subscribePostAuthor($event);
        $this->assertSame($post, $user->getPostSubscriptions()->first());
    }

    public function testSubscribePetitionAuthor()
    {
        $user = new User();
        $petition = new UserPetition();
        $petition->setUser($user);
        $subscriber = $this->getLeaderContentSubscriberForSubscribeAuthor();
        $event = new UserPetitionEvent($petition);
        $subscriber->subscribePetitionAuthor($event);
        $this->assertSame($petition, $user->getPetitionSubscriptions()->first());
    }

    /**
     * @param array $methods
     * @return \PHPUnit_Framework_MockObject_MockObject|Settings
     */
    private function getSettingsMock(array $methods = []): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->getMockBuilder(Settings::class)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * @param array $methods
     * @return \PHPUnit_Framework_MockObject_MockObject|CommentManager
     */
    private function getCommentManagerMock(array $methods = []): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->getMockBuilder(CommentManager::class)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }

    private function getLeaderContentSubscriberForAddHashTagsTest(HashTaggableInterface $entity)
    {
        $repository = $this->getMockBuilder(HashTagRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['addForTaggableEntity'])
            ->getMock();
        $repository->expects($this->once())
            ->method('addForTaggableEntity')
            ->with($entity, true);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(HashTag::class)
            ->willReturn($repository);
        $settings = $this->getSettingsMock();
        $manager = $this->getCommentManagerMock();

        return new LeaderContentSubscriber($em, $settings, $manager);
    }

    private function getLeaderContentSubscriberForSubscribeAuthor()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('flush')
            ->with();
        $settings = $this->getSettingsMock();
        $manager = $this->getCommentManagerMock();

        return new LeaderContentSubscriber($em, $settings, $manager);
    }
}