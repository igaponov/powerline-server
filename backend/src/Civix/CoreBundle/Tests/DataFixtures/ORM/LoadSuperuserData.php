<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Superuser;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * LoadSuperuserData.
 */
class LoadSuperuserData extends AbstractFixture implements FixtureInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        $factory = $this->container->get('security.encoder_factory');

        $users [] = ['username' => 'admin'];
        for($i = 1; $i <= 10; $i++)
        {
        	$users [] = ['username' => 'superuser' . $i];
        }

        foreach ($users as $data) 
        {
            $user = new Superuser();

            $encoder = $factory->getEncoder($user);
            $password = $encoder->encodePassword($data['username'], $user->getSalt());

            $user->setUsername($data['username'])
                ->setEmail($data['username'].'@example.com')
                ->setPassword($password);
            
            $user->generateToken();

            $this->addReference('superuser-'.$data['username'], $user);

            $manager->persist($user);
        }

        $manager->flush();
    }
}
