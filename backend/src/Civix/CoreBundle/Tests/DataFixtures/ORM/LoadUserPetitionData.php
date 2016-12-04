<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserPetition;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadUserPetitionData extends AbstractFixture implements DependentFixtureInterface
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
        /** @var User $user3 */
        $user3 = $this->getReference('user_3');
        $group1 = $this->getReference('group_1');
        $group2 = $this->getReference('group_2');

        $petition = new UserPetition();
        $petition->setUser($user1)
            ->setTitle('Mom stands up to protect anti-bullying laws')
            ->setBody('Danielle Green\'s daughter took her own life after being tormented by bullies. Danielle started a petition to protect Indiana\'s anti-bullying laws, and after 235,000 signatures, lawmakers ceased efforts to weaken the laws.')
            ->boost()
            ->setOrganizationNeeded(true)
            ->setGroup($group1);
        $manager->persist($petition);
        $this->addReference('user_petition_1', $petition);

        $petition = new UserPetition();
        $petition->setUser($user1)
            ->setTitle('Whole Foods takes major step on food waste')
            ->setBody('A campaign supported by more than 110,000 people helped push grocery chain Whole Foods Market to join the fight against food waste in the U.S.')
            ->setGroup($group2);
        $user1->addPetitionSubscription($petition);
        $manager->persist($petition);
        $this->addReference('user_petition_2', $petition);

        $petition = new UserPetition();
        $petition->setUser($user2)
            ->setTitle('
Women WWII pilots get burial rights at Arlington National Cemetery')
            ->setBody('The U.S. Army wouldn’t let female WWII pilots like Tiffany’s grandmother be laid to rest at Arlington National Cemetery. 175,000 signatures later, Tiffany convinced Congress and the President to change the law.')
            ->boost()
            ->setOrganizationNeeded(true)
            ->setGroup($group1);
        $user2->addPetitionSubscription($petition);
        $manager->persist($petition);
        $this->addReference('user_petition_3', $petition);

        $petition = new UserPetition();
        $petition->setUser($user3)
            ->setTitle('')
            ->setBody('John Feal led a movement to pass the Zadroga Act to give healthcare coverage to 9/11 first responders and survivors. His campaign included a petition with more than 180,000 signatures.')
            ->boost()
            ->setGroup($group2);
        $user3->addPetitionSubscription($petition);
        $manager->persist($petition);
        $this->addReference('user_petition_4', $petition);

        $petition = new UserPetition();
        $petition->setUser($user3)
            ->setTitle('Congress passes landmark disability rights bill')
            ->setBody('Sara Wolff led a campaign signed by more than 265,000 people, urging Congress to help people with disabilities take more control over their finances.')
            ->boost()
            ->setGroup($group1);
        $manager->persist($petition);
        $this->addReference('user_petition_5', $petition);

        $petition = new UserPetition();
        $petition->setUser($user3)
            ->setTitle('Commit to act for paid family leave for all in your first 100 days')
            ->setBody('Under ordinary circumstances, two mothers as different as we are would never have met. One of us is from Oklahoma and is a registered Republican. The other is an unmarried liberal who lives in Brooklyn.')
            ->boost()
            ->setOrganizationNeeded(true)
            ->setGroup($group1)
            ->setSupportersWereInvited(true);
        $user3->addPetitionSubscription($petition);
        $manager->persist($petition);
        $this->addReference('user_petition_6', $petition);

        $deleted = new UserPetition();
        $deleted->setUser($user3)
            ->setTitle('-- deleted --')
            ->setBody('-- deleted --')
            ->boost()
            ->setGroup($group1);
        $manager->persist($deleted);
        $this->addReference('user_petition_7', $deleted);

        $manager->flush();

        $manager->remove($deleted);
        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadGroupData::class];
    }
}
