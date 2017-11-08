<?php

namespace Tests\Civix\CoreBundle\Repository;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostVoteData;

class PostRepositoryTest extends WebTestCase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::bootFixtureLoader();
        self::$fixtureLoader->loadFixtures([
            LoadPostVoteData::class,
        ]);
    }

    public function testFindPostWithUserVote()
    {
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('user_1');
        $post = $repository->getReference('post_1');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $result = $em->getRepository(Post::class)->findPostWithUserVote($post->getId(), $user);
        $this->assertInstanceOf(Post::class, $result);
        $this->assertCount(1, $result->getVotes());
    }

    public function testFindPostWithEmptyUserVote()
    {
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('user_4');
        $post = $repository->getReference('post_1');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $result = $em->getRepository(Post::class)->findPostWithUserVote($post->getId(), $user);
        $this->assertInstanceOf(Post::class, $result);
        $this->assertCount(0, $result->getVotes());
    }

    public function testFindPostWithUserVoteReturnsNull()
    {
        $repository = self::$fixtureLoader->executor->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('user_1');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $result = $em->getRepository(Post::class)->findPostWithUserVote(0, $user);
        $this->assertNull($result);
    }
}
