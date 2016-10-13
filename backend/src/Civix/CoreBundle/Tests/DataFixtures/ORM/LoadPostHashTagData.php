<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Post;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadPostHashTagData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        /** @var Post $post */
        $post = $this->getReference('post_1');
        $tag1 = $this->getReference('tag_1');
        $tag2 = $this->getReference('tag_2');
        $tag3 = $this->getReference('tag_3');

        $post->addHashTag($tag1);
        $post->addHashTag($tag2);
        $post->addHashTag($tag3);

        $manager->persist($post);
        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadPostData::class, LoadHashTagData::class];
    }
}
