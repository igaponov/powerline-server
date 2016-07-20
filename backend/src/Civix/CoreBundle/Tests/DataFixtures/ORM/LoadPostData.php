<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Content\Post;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

class LoadPostData extends AbstractFixture implements DependentFixtureInterface
{
    /** @var ObjectManager */
    private $manager;

    public function load(ObjectManager $manager)
    {
        $this->manager = $manager;

        $this->addReference('post_1', $this->createPost());
        $this->addReference('post_2', $this->createPost());
        $this->addReference('post_3', $this->createPost());
    }

    private function createPost()
    {
        $faker = Factory::create();
        $post = new Post();
        $post->setContent($faker->text);
        $post->setIsPublished(true);
        $post->setPublishedAt(new \DateTime());
        $post->setShortDescription($faker->text(50));
        $post->setTitle($faker->text(25));

        $this->manager->persist($post);
        $this->manager->flush();

        return $post;
    }

    /**
     * This method must return an array of fixtures classes
     * on which the implementing class depends on
     *
     * @return array
     */
    function getDependencies()
    {
        return [];
    }
}