<?php
namespace Civix\CoreBundle\Tests\Service\Group;

use Civix\CoreBundle\Entity\Report\UserReport;
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

        /** @var \PHPUnit_Framework_MockObject_MockObject|Geocode $geocode */
        $geocode = $this->createMock(Geocode::class);
        $geocode->expects($this->once())
            ->method('getCountry')
            ->will($this->returnValue(new AddressComponent('Mozambique', 'MZ')));
        $geocode->expects($this->once())
            ->method('getState')
            ->will($this->returnValue(new AddressComponent('Zambezia Province', 'Zambezia Province')));
        $geocode->expects($this->once())
            ->method('getLocality')
            ->will($this->returnValue(new AddressComponent('Chinde', 'Chinde')));
        $groupManager = new GroupManager(
            $this->container->get('doctrine')->getManager(),
            $geocode,
            $this->container->get('event_dispatcher')
        );
        $groupManager->autoJoinUser($this->user);

        /** @var UserGroup[] $groups */
        $groups = $this->em
            ->getRepository(UserGroup::class)
            ->findBy(array('user' => $this->user));

        $this->assertSame('MZ', $groups[0]->getGroup()->getLocationName());
        $this->assertSame('Zambezia Province', $groups[1]->getGroup()->getLocationName());
        $this->assertSame('Chinde', $groups[2]->getGroup()->getLocationName());

        $report = $this->em->getRepository(UserReport::class)
            ->getUserReport($this->user);
        $this->assertSame('Mozambique', $report[0]['country']);
        $this->assertSame('Zambezia Province', $report[0]['state']);
        $this->assertSame('Chinde', $report[0]['locality']);
    }
}