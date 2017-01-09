<?php
namespace Civix\CoreBundle\Tests\EventListener;

use Civix\CoreBundle\Entity\Activities\UserPetition;
use Civix\CoreBundle\Entity\Metadata;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Civix\ApiBundle\Tests\WebTestCase;

class MentionSubscriberTest extends WebTestCase
{
    public function testParseActivityBody()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
        ])->getReferenceRepository();
        $user = $repository->getReference('user_2');
        $username = $user->getUsername();
        $activity = new UserPetition();
        $activity->setTitle('Title')
            ->setDescription('Hello, @'.$username.'!')
            ->setResponsesCount(2)
            ->setOwner([]);
        $manager = $this->getContainer()->get('doctrine')->getManager();
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
        $activity = new UserPetition();
        $activity->setTitle('Title')
            ->setDescription('Hello, @'.$username.'!')
            ->setResponsesCount(2)
            ->setOwner([]);
        $manager = $this->getContainer()->get('doctrine')->getManager();
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
        /** @var User $user */
        $user = $repository->getReference('user_2');
        $group = $repository->getReference('group_2');
        $username = $user->getUsername();
        $petition = new \Civix\CoreBundle\Entity\UserPetition();
        $petition->setTitle('Title')
            ->setUser($user)
            ->setGroup($group)
            ->setBody('Hello, @'.$username.'!')
            ->setMetadata(new Metadata());
        $manager = $this->getContainer()->get('doctrine')->getManager();
        $manager->persist($petition);
        $manager->flush();
        $this->assertEquals(
            'Hello, <a data-user-id="'.$user->getId().'">@'.$username.'</a>!',
            $petition->getHtmlBody()
        );
    }

    public function testParsePetitionBodyWithNonExistentUsername()
    {
        $repository = $this->loadFixtures([
            LoadGroupData::class,
        ])->getReferenceRepository();
        /** @var User $user */
        $user = $repository->getReference('user_2');
        $group = $repository->getReference('group_2');
        $username = 'mention';
        $activity = new \Civix\CoreBundle\Entity\UserPetition();
        $activity->setTitle('Title')
            ->setUser($user)
            ->setGroup($group)
            ->setBody('Hello, @'.$username.'!')
            ->setMetadata(new Metadata());
        $manager = $this->getContainer()->get('doctrine')->getManager();
        $manager->persist($activity);
        $manager->flush();
        $this->assertEquals(
            'Hello, @mention!',
            $activity->getHtmlBody()
        );
    }
}