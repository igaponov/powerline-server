<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Civix\CoreBundle\Entity\User;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

/**
 * LoadUserData.
 */
class LoadDisabledUserData extends AbstractFixture implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /** @var  ObjectManager */
    private $manager;

    public function setContainer(ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;

        $user = new User();
        $user->setUsername('userD')
            ->setFirstName('User')
            ->setLastName('Disabled')
            ->setEmail('userD@example.com')
            ->setPlainPassword('userD')
            ->setBirth(new \DateTime('-25 years'));

        $this->encodePassword($user);
        $this->addReference('user_disabled', $user);
        $manager->persist($user);
        $manager->flush();
    }

    private function encodePassword(User $user): void
    {
        /** @var PasswordEncoderInterface $encoder */
        $encoder = $this->container->get('security.encoder_factory')->getEncoder($user);
        $password = $encoder->encodePassword($user->getPlainPassword(), $user->getSalt());
        $user->setPassword($password);
    }
}
