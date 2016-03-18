<?php
namespace Civix\CoreBundle\Tests\Service\Group;

use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserGroup;
use Civix\CoreBundle\Service\Group\GroupManager;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

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

    public function testAutoJoinUserAFU()
    {
        $this->user->setAddress1('Sudirman St')
            ->setCity('Chinde District')
            ->setState('Zambezia')
            ->setCountry('MZ');
        $this->em->persist($this->user);
        $this->em->flush();

        /** @var GroupManager $groupManager */
        $groupManager = $this->container->get('civix_core.group_manager');
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