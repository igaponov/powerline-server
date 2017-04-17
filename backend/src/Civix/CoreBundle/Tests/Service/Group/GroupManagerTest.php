<?php
namespace Civix\CoreBundle\Tests\Service\Group;

use Civix\CoreBundle\Entity\User;
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

    public function testAutoJoinUserAFU()
    {
        $manager = $this->getContainer()->get('civix_core.group_manager');
        $this->assertFalse(method_exists($manager, 'autoJoinUser'), 'Method is deleted');
    }
}