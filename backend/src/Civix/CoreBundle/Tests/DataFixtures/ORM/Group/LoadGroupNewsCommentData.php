<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM\Group;

use Civix\CoreBundle\Entity\Poll\Comment;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

class LoadGroupNewsCommentData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var User $user */
        $user = $this->getReference('user_2');
        /** @var Question\GroupNews $news */
        $news = $this->getReference('group_news_1');

        // root
        $root = new Comment($news->getUser());
        $root->setQuestion($news);
        $root->setCommentBody($news->getSubject());
        $root->setCommentBodyHtml($news->getSubjectParsed());
        $manager->persist($root);
        $this->addReference('group_news_1_comment_1', $root);

        // user's
        $faker = Factory::create();
        $comment = new Comment($user, $root);
        $comment->setQuestion($news);
        $comment->setCommentBody($faker->text);
        $comment->setCommentBodyHtml($faker->text);
        $manager->persist($comment);
        $this->addReference('group_news_1_comment_2', $comment);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadGroupNewsData::class];
    }
}