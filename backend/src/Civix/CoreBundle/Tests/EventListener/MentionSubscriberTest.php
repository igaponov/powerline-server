<?php
namespace Civix\CoreBundle\Tests\EventListener;

use Civix\CoreBundle\Entity\Activities\MicroPetition;
use Civix\CoreBundle\Entity\Micropetitions\Metadata;
use Civix\CoreBundle\Entity\Micropetitions\Petition;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Liip\FunctionalTestBundle\Test\WebTestCase;

class MentionSubscriberTest extends WebTestCase
{
    public function testParseActivityBody()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
        ])->getReferenceRepository();
        $user = $repository->getReference('user_2');
        $username = $user->getUsername();
        $activity = new MicroPetition();
        $activity->setTitle('Title')
            ->setDescription('Hello, @'.$username.'!')
            ->setResponsesCount(2)
            ->setOwner([]);
        $manager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $manager->persist($activity);
        $manager->flush();
        $this->assertEquals(
            'Hello, <a data-user-id="'.$user->getId().'">@'.$username.'</a>!',
            $activity->getDescriptionHtml()
        );
    }

    public function testParseActivityBodyWithNonExistentUsername()
    {
        $this->loadFixtures([
            LoadUserData::class,
        ]);
        $username = 'mention';
        $activity = new MicroPetition();
        $activity->setTitle('Title')
            ->setDescription('Hello, @'.$username.'!')
            ->setResponsesCount(2)
            ->setOwner([]);
        $manager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $manager->persist($activity);
        $manager->flush();
        $this->assertEquals(
            'Hello, @mention!',
            $activity->getDescriptionHtml()
        );
    }

    public function testParsePetitionBody()
    {
        $repository = $this->loadFixtures([
            LoadGroupData::class,
        ])->getReferenceRepository();
        $user = $repository->getReference('user_2');
        $group = $repository->getReference('group_2');
        $username = $user->getUsername();
        $activity = new Petition();
        $activity->setTitle('Title')
            ->setGroup($group)
            ->setPetitionBody('Hello, @'.$username.'!')
            ->setIsOutsidersSign(true)
            ->setExpireAt(new \DateTime())
            ->setUserExpireInterval(1)
            ->setPublishStatus(Petition::STATUS_PUBLISH)
            ->setMetadata(new Metadata());
        $manager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $manager->persist($activity);
        $manager->flush();
        $this->assertEquals(
            'Hello, <a data-user-id="'.$user->getId().'">@'.$username.'</a>!',
            $activity->getPetitionBodyHtml()
        );
    }

    public function testParsePetitionBodyWithNonExistentUsername()
    {
        $repository = $this->loadFixtures([
            LoadGroupData::class,
        ])->getReferenceRepository();
        $group = $repository->getReference('group_2');
        $username = 'mention';
        $activity = new Petition();
        $activity->setTitle('Title')
            ->setGroup($group)
            ->setPetitionBody('Hello, @'.$username.'!')
            ->setIsOutsidersSign(true)
            ->setExpireAt(new \DateTime())
            ->setUserExpireInterval(1)
            ->setPublishStatus(Petition::STATUS_PUBLISH)
            ->setMetadata(new Metadata());
        $manager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $manager->persist($activity);
        $manager->flush();
        $this->assertEquals(
            'Hello, @mention!',
            $activity->getPetitionBodyHtml()
        );
    }
}