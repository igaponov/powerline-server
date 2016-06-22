<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Micropetitions\Petition;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * LoadUserGroupData.
 *
 * @author Habibillah <habibillah@gmail.com>
 */
class LoadMicropetitionData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $user1 = $this->getReference('userfollowtest1');
        $user2 = $this->getReference('userfollowtest2');
        $user3 = $this->getReference('userfollowtest3');
        $privateGroup = $this->getReference('testfollowprivategroups');
        $secretGroup = $this->getReference('testfollowsecretgroups');

        $petition = new Petition();
        $petition->setUser($user1)
            ->setTitle('Mom stands up to protect anti-bullying laws')
            ->setPetitionBody('Danielle Green\'s daughter took her own life after being tormented by bullies. Danielle started a petition to protect Indiana\'s anti-bullying laws, and after 235,000 signatures, lawmakers ceased efforts to weaken the laws.')
            ->setLink('https://www.change.org/p/indiana-state-senate-protect-our-children-do-not-pass-senate-bill-500')
            ->setCreatedAt(new \DateTime('-5 months'))
            ->setExpireAt(new \DateTime('+180 days'))
            ->setPublishStatus(Petition::STATUS_PUBLISH)
            ->setType(Petition::TYPE_OPEN_LETTER)
            ->setUserExpireInterval(180)
            ->setGroup($privateGroup);
        $manager->persist($petition);
        $this->addReference('micropetition_1', $petition);

        $petition = new Petition();
        $petition->setUser($user1)
            ->setTitle('Whole Foods takes major step on food waste')
            ->setPetitionBody('A campaign supported by more than 110,000 people helped push grocery chain Whole Foods Market to join the fight against food waste in the U.S.')
            ->setLink('https://www.change.org/p/whole-foods-and-walmart-stop-contributing-to-massive-food-waste-in-the-us-31')
            ->setCreatedAt(new \DateTime('-3 months'))
            ->setExpireAt(new \DateTime('+80 days'))
            ->setPublishStatus(Petition::STATUS_USER)
            ->setType(Petition::TYPE_QUORUM)
            ->setUserExpireInterval(80)
            ->setGroup($secretGroup);
        $manager->persist($petition);
        $this->addReference('micropetition_2', $petition);

        $petition = new Petition();
        $petition->setUser($user2)
            ->setTitle('
Women WWII pilots get burial rights at Arlington National Cemetery')
            ->setPetitionBody('The U.S. Army wouldn’t let female WWII pilots like Tiffany’s grandmother be laid to rest at Arlington National Cemetery. 175,000 signatures later, Tiffany convinced Congress and the President to change the law.')
            ->setLink('https://www.change.org/p/patrick-k-hallinan-department-of-army-grant-military-burial-honors-to-women-wwii-pilots')
            ->setCreatedAt(new \DateTime('-4 months'))
            ->setExpireAt(new \DateTime('+100 days'))
            ->setPublishStatus(Petition::STATUS_PUBLISH)
            ->setType(Petition::TYPE_OPEN_LETTER)
            ->setUserExpireInterval(100)
            ->setGroup($privateGroup);
        $manager->persist($petition);
        $this->addReference('micropetition_3', $petition);

        $petition = new Petition();
        $petition->setUser($user3)
            ->setTitle('')
            ->setPetitionBody('John Feal led a movement to pass the Zadroga Act to give healthcare coverage to 9/11 first responders and survivors. His campaign included a petition with more than 180,000 signatures.')
            ->setLink('https://www.change.org/p/tell-congress-we-will-never-forget-9-11-first-responders-and-survivors')
            ->setCreatedAt(new \DateTime('-7 months'))
            ->setExpireAt(new \DateTime('+40 days'))
            ->setPublishStatus(Petition::STATUS_PUBLISH)
            ->setType(Petition::TYPE_LONG_PETITION)
            ->setUserExpireInterval(40)
            ->setGroup($secretGroup);
        $manager->persist($petition);
        $this->addReference('micropetition_4', $petition);

        $petition = new Petition();
        $petition->setUser($user3)
            ->setTitle('Congress passes landmark disability rights bill')
            ->setPetitionBody('Sara Wolff led a campaign signed by more than 265,000 people, urging Congress to help people with disabilities take more control over their finances.')
            ->setLink('https://www.change.org/p/congress-pass-the-able-act')
            ->setCreatedAt(new \DateTime('-6 months'))
            ->setExpireAt(new \DateTime('+200 days'))
            ->setPublishStatus(Petition::STATUS_PUBLISH)
            ->setType(Petition::TYPE_QUORUM)
            ->setUserExpireInterval(200)
            ->setGroup($privateGroup);
        $manager->persist($petition);
        $this->addReference('micropetition_5', $petition);

        $petition = new Petition();
        $petition->setUser($user3)
            ->setTitle('Commit to act for paid family leave for all in your first 100 days')
            ->setPetitionBody('Under ordinary circumstances, two mothers as different as we are would never have met. One of us is from Oklahoma and is a registered Republican. The other is an unmarried liberal who lives in Brooklyn.')
            ->setLink('https://www.change.org/p/candidates-for-u-s-president-if-elected-commit-to-act-for-paid-family-leave-for-all-in-your-first-100-days-babysfirst100?source_location=discover_feed')
            ->setCreatedAt(new \DateTime('-12 months'))
            ->setExpireAt(new \DateTime('-10 days'))
            ->setPublishStatus(Petition::STATUS_PUBLISH)
            ->setType(Petition::TYPE_OPEN_LETTER)
            ->setUserExpireInterval(110)
            ->setGroup($privateGroup);
        $manager->persist($petition);
        $this->addReference('micropetition_6', $petition);

        $deleted = new Petition();
        $deleted->setUser($user3)
            ->setTitle('-- deleted --')
            ->setPetitionBody('-- deleted --')
            ->setLink('https://www.change.org')
            ->setCreatedAt(new \DateTime('-2 months'))
            ->setExpireAt(new \DateTime('+10 days'))
            ->setPublishStatus(Petition::STATUS_PUBLISH)
            ->setType(Petition::TYPE_QUORUM)
            ->setUserExpireInterval(10)
            ->setGroup($privateGroup);
        $manager->persist($deleted);
        $this->addReference('micropetition_7', $deleted);

        $manager->flush();

        $manager->remove($deleted);
        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadUserData::class, LoadGroupData::class];
    }
}
