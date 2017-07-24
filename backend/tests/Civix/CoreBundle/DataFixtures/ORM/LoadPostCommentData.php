<?php

namespace Tests\Civix\CoreBundle\DataFixtures\ORM;

use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\Post\Comment;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostData;
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
        $user1 = $this->getReference('user_1');
        $user2 = $this->getReference('user_2');
        $user3 = $this->getReference('user_3');
        $user4 = $this->getReference('user_4');
        /** @var Post $post1 */
        $post1 = $this->getReference('post_1');
        /** @var Post $post5 */
        $post5 = $this->getReference('post_5');

        $comment1 = (new Comment())
            ->setPost($post1)
            ->setCommentBody('Test for comment body 1')
            ->setCommentBodyHtml('<div>Comment Body HTML 1</div>')
            ->setUser($user1);
        $manager->persist($comment1);

        $comment2 = (new Comment())
            ->setPost($post1)
            ->setCommentBody('Test for comment body 2')
            ->setCommentBodyHtml('<div>Comment Body HTML 2</div>')
            ->setUser($user2)
            ->setPrivacy(BaseComment::PRIVACY_PRIVATE);
        $manager->persist($comment2);


        $comment3 = (new Comment())
            ->setPost($post5)
            ->setCommentBody('Test for comment body 3')
            ->setCommentBodyHtml('<div>Comment Body HTML 3</div>')
            ->setUser($user2);
        $manager->persist($comment3);

        $comment4 = (new Comment())
            ->setPost($post1)
            ->setCommentBody('Test for comment body 4')
            ->setCommentBodyHtml('<div>Comment Body HTML 4</div>')
            ->setUser($user1)
            ->setParentComment($comment1);
        $manager->persist($comment4);

        $comment5 = (new Comment())
            ->setPost($post1)
            ->setCommentBody('Test for comment body 5')
            ->setCommentBodyHtml('<div>Comment Body HTML 5</div>')
            ->setUser($user3)
            ->setParentComment($comment1)
            ->setPrivacy(BaseComment::PRIVACY_PRIVATE);
        $manager->persist($comment5);

        $comment6 = (new Comment())
            ->setPost($post1)
            ->setCommentBody('Test for comment body 6')
            ->setCommentBodyHtml('<div>Comment Body HTML 6</div>')
            ->setUser($user4)
            ->setParentComment($comment1);
        $manager->persist($comment6);

        $manager->flush();

        $comment7 = (new Comment())
            ->setPost($post1)
            ->setCommentBody('Test for comment body 7')
            ->setCommentBodyHtml('<div>Comment Body HTML 7</div>')
            ->setUser($user1)
            ->setParentComment($comment2);
        $manager->persist($comment7);

        $manager->flush();

        $this->addReference('post_comment_1', $comment1);
        $this->addReference('post_comment_2', $comment2);
        $this->addReference('post_comment_3', $comment3);
        $this->addReference('post_comment_4', $comment4);
        $this->addReference('post_comment_5', $comment5);
        $this->addReference('post_comment_6', $comment6);
        $this->addReference('post_comment_7', $comment7);
    }

    public function getDependencies()
    {
        return [LoadPostData::class];
    }
}
