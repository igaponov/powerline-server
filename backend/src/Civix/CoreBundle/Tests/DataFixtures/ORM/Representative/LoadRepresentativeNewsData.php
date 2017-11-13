<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM\Representative;

use Civix\CoreBundle\Entity\Poll\Question\RepresentativeNews;
use Civix\CoreBundle\Entity\UserRepresentative;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserRepresentativeData;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

class LoadRepresentativeNewsData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();

        /** @var UserRepresentative $representative */
        $representative = $this->getReference('representative_jb');

        $question = new RepresentativeNews();
        $question->setOwner($representative);
        $question->setUser($representative->getUser());
        $question->setSubject('subj '.$faker->sentence);
        $representative->getUser()->addPollSubscription($question);
        $manager->persist($question);
        $this->addReference('representative_news_1', $question);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadUserRepresentativeData::class];
    }
}