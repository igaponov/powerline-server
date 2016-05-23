<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Civix\CoreBundle\Entity\User;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

/**
 * LoadUserData.
 */
class LoadUserData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /** @var  ObjectManager */
    private $manager;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        $this->manager = $manager;

        $this->generateUser('mobile1');
        $this->generateUser('mobile2');

        $this->addReference('followertest', $this->generateUser('followertest', null, ['district_la', 'district_sf']));
        $this->addReference('userfollowtest1', $this->generateUser('userfollowtest1', null, 'district_sd'));
        $this->addReference('userfollowtest2', $this->generateUser('userfollowtest2'));
        $this->addReference('userfollowtest3', $this->generateUser('userfollowtest3'));
        $this->addReference('testuserbookmark1', $this->generateUser('testuserbookmark1'));
    }

    /**
     * @param $username
     * @param null $birthDate
     * @param null $district
     * @return User
     */
    private function generateUser($username, $birthDate = null, $district = null)
    {
        $birthDate = $birthDate ?: new \DateTime();

        $user = new User();
        $user->setUsername($username)
            ->setEmail("$username@example.com")
            ->setPlainPassword($username)
            ->setBirth($birthDate)
            ->setDoNotDisturb(true)
            ->setIsNotifDiscussions(false)
            ->setIsNotifMessages(false)
            ->setIsRegistrationComplete(true)
            ->setPhone(date_create()->getOffset())
            ->setIsNotifOwnPostChanged(false)
            ->setSalt(base_convert(sha1(uniqid(mt_rand(), true)), 16, 36))
            ->setToken($username);
        
        if (is_array($district)) {
            foreach ($district as $item) {
                $user->addDistrict($this->getReference($item));
            }
        } elseif ($district !== null) {
            $user->addDistrict($this->getReference($district));
        }

        /** @var PasswordEncoderInterface $encoder */
        $encoder = $this->container->get('security.encoder_factory')->getEncoder($user);
        $password = $encoder->encodePassword($user->getPlainPassword(), $user->getSalt());
        $user->setPassword($password);

        $this->manager->persist($user);
        $this->manager->flush();

        return $user;
    }

    public function getDependencies()
    {
        return [LoadDistrictData::class];
    }
}
