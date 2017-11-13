<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\Post\Comment;
use Civix\CoreBundle\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadPostCommentData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        /** @var User $user1 */
        $user1 = $this->getReference('user_1');
        /** @var User $user2 */
        $user2 = $this->getReference('user_2');
        /** @var Post $post1 */
        $post1 = $this->getReference('post_1');
        /** @var Post $post5 */
        $post5 = $this->getReference('post_5');

        $comment1 = new Comment($user1);
        $comment1->setCommentBody('Test for comment body 1');
        $comment1->setCommentBodyHtml('<div>Comment Body HTML 1</div>');
        $comment1->setPost($post1);
        $manager->persist($comment1);

        $comment2 = new Comment($user1);
        $comment2->setCommentBody('Test for comment body 2');
        $comment2->setCommentBodyHtml('<div>Comment Body HTML 2</div>');
        $comment2->setPost($post5);
        $manager->persist($comment2);


        $comment3 = new Comment($user2, $comment2);
        $comment3->setCommentBody('Test for comment body 3');
        $comment3->setCommentBodyHtml('<div>Comment Body HTML 3</div>');
        $comment3->setPost($post5);
        $manager->persist($comment3);

        $manager->flush();

        $this->addReference('post_comment_1', $comment1);
        $this->addReference('post_comment_2', $comment2);
        $this->addReference('post_comment_3', $comment3);
    }

    public function getDependencies()
    {
        return [LoadPostData::class];
    }
}
