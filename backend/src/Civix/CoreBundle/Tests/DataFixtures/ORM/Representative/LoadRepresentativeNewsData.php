<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM\Representative;

use Civix\CoreBundle\Entity\Poll\Question\RepresentativeNews;
use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadRepresentativeData;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

class LoadRepresentativeNewsData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();

        /** @var Representative $representative */
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
        return [LoadRepresentativeData::class];
    }
}