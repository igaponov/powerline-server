<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Micropetitions\Answer;
use Civix\CoreBundle\Entity\Micropetitions\Comment;
use Civix\CoreBundle\Entity\Micropetitions\Petition;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * LoadUserGroupData.
 *
 * @author Habibillah <habibillah@gmail.com>
 */
class LoadMicropetitionCommentData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $user1 = $this->getReference('userfollowtest1');
        $user2 = $this->getReference('userfollowtest2');
        $petition1 = $this->getReference('micropetition_1');
        $petition5 = $this->getReference('micropetition_5');

        $comment1 = new Comment();
        $comment1->setCommentBody("Test for comment body 1");
        $comment1->setCommentBodyHtml("<div>Comment Body HTML 1</div>");
        $comment1->setCreatedAt(new \DateTime());
        $comment1->setPetition($petition1);
        $comment1->setUser($user1);
        $manager->persist($comment1);

        $comment2 = new Comment();
        $comment2->setCommentBody("Test for comment body 2");
        $comment2->setCommentBodyHtml("<div>Comment Body HTML 2</div>");
        $comment2->setCreatedAt(new \DateTime());
        $comment2->setPetition($petition5);
        $comment2->setUser($user1);
        $manager->persist($comment2);


        $comment3 = new Comment();
        $comment3->setCommentBody("Test for comment body 3");
        $comment3->setCommentBodyHtml("<div>Comment Body HTML 3</div>");
        $comment3->setCreatedAt(new \DateTime());
        $comment3->setPetition($petition5);
        $comment3->setUser($user2);
        $manager->persist($comment3);

        $manager->flush();



        $this->addReference('micropetition_comment_1', $comment1);
        $this->addReference('micropetition_comment_2', $comment2);
        $this->addReference('micropetition_comment_3', $comment3);
    }

    public function getDependencies()
    {
        return [LoadUserData::class, LoadMicropetitionData::class];
    }
}
