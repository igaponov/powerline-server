<?php
namespace Civix\CoreBundle\Tests\Service\Group;

use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserGroup;
use Civix\CoreBundle\Model\Geocode\AddressComponent;
use Civix\CoreBundle\Service\Google\Geocode;
use Civix\CoreBundle\Service\Group\GroupManager;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Civix\ApiBundle\Tests\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @author Habibillah <habibillah@gmail.com>
 */
class GroupManagerTest extends WebTestCase
{
    /** @var \Doctrine\ORM\EntityManager */
    private $em;

    /** @var  ContainerInterface */
    private $container;

    /** @var  User */
    private $user;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->container = $this->getContainer();
        $this->em = $this->container->get('doctrine')->getManager();

        /** @var AbstractExecutor $fixtures */
        $fixtures = $this->loadFixtures([LoadUserData::class]);
        $reference = $fixtures->getReferenceRepository();

        $this->user = $reference->getReference('testuserbookmark1');
    }

    protected function tearDown()
    {
        $this->em = null;
        $this->container = null;
        $this->user = null;
        parent::tearDown();
    }

    /**
     * @author Habibillah <habibillah@gmail.com>
     */
    public function testAutoJoinUserAFU()
    {
        $this->user->setAddress1('Sudirman St')
            ->setCity('Chinde District')
            ->setState('Zambezia')
            ->setCountry('MZ');
        $this->em->persist($this->user);
        $this->em->flush();

        /** @var GroupManager $groupManager */
        $geocode = $this->getMock(Geocode::class);
        $geocode->expects($this->once())
            ->method('getCountry')
            ->will($this->returnValue(new AddressComponent('Mozambique', 'MZ')));
        $geocode->expects($this->once())
            ->method('getState')
            ->will($this->returnValue(new AddressComponent('Zambezia Province', 'Zambezia Province')));
        $geocode->expects($this->once())
            ->method('getLocality')
            ->will($this->returnValue(new AddressComponent('Chinde', 'Chinde')));
        $groupManager = $this->getMock(
            GroupManager::class,
            null,
            [
                $this->container->get('doctrine.orm.entity_manager'),
                $geocode,
                $this->container->get('event_dispatcher')
            ]
        );
        $groupManager->autoJoinUser($this->user);

        /** @var UserGroup $groups */
        $groups = $this->em
            ->getRepository(UserGroup::class)
            ->findBy(array('user' => $this->user));

        $groupLocations = array();
        foreach($groups as $item)
            $groupLocations[] = $item->getGroup()->getLocationName();

        $this->assertContains('MZ', $groupLocations);
    }
}