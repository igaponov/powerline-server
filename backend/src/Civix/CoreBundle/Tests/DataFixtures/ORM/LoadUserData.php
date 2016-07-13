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

        $user = new User();
        $user->setUsername('user1')
            ->setFirstName('User')
            ->setLastName('One')
            ->setEmail("user1@example.com")
            ->setPlainPassword('user1')
            ->setBirth(new \DateTime('-30 years'))
            ->setDoNotDisturb(true)
            ->setIsNotifDiscussions(false)
            ->setIsNotifMessages(false)
            ->setIsRegistrationComplete(true)
            ->setPhone('+1234567890')
            ->setIsNotifOwnPostChanged(true)
            ->setSalt(base_convert(sha1(uniqid(mt_rand(), true)), 16, 36))
            ->setToken('user1')
            ->setSlogan('User 1 Slogan')
            ->setBio('User 1 Bio');

        $this->encodePassword($user);
        $this->addReference('user_1', $user);
        $manager->persist($user);
        $manager->flush();

        $this->addReference('user_2', $this->generateUser('user2'));
        $this->addReference('user_3', $this->generateUser('user3'));
        $this->addReference('user_4', $this->generateUser('user4'));

        $this->addReference('followertest', $this->generateUser('followertest', null, ['district_la', 'district_sf']));
        $this->addReference('userfollowtest1', $this->generateUser('userfollowtest1', null, 'district_sd', true));
        $this->addReference('userfollowtest2', $this->generateUser('userfollowtest2'));
        $this->addReference('userfollowtest3', $this->generateUser('userfollowtest3'));
        $this->addReference('testuserbookmark1', $this->generateUser('testuserbookmark1'));
    }

    /**
     * @param $username
     * @param null $birthDate
     * @param null $district
     * @param bool $isNotifOwnPostChanged
     * @return User
     */
    private function generateUser($username, $birthDate = null, $district = null, $isNotifOwnPostChanged = false)
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
            ->setIsNotifOwnPostChanged($isNotifOwnPostChanged)
            ->setSalt(base_convert(sha1(uniqid(mt_rand(), true)), 16, 36))
            ->setToken($username);
        
        if (is_array($district)) {
            foreach ($district as $item) {
                $user->addDistrict($this->getReference($item));
            }
        } elseif ($district !== null) {
            $user->addDistrict($this->getReference($district));
        }

        $this->encodePassword($user);

        $this->manager->persist($user);
        $this->manager->flush();

        return $user;
    }

    private function encodePassword(User $user)
    {
        /** @var PasswordEncoderInterface $encoder */
        $encoder = $this->container->get('security.encoder_factory')->getEncoder($user);
        $password = $encoder->encodePassword($user->getPlainPassword(), $user->getSalt());
        $user->setPassword($password);
    }

    public function getDependencies()
    {
        return [LoadDistrictData::class];
    }
}
