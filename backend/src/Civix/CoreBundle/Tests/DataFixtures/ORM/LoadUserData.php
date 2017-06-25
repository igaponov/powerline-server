<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\District;
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

    public function setContainer(ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;

        $user = new User();
        $user->setUsername('user1')
            ->setFirstName('User')
            ->setLastName('One')
            ->setEmail('user1@example.com')
            ->setPlainPassword('user1')
            ->setBirth(new \DateTime('-30 years'))
            ->setDoNotDisturb(false)
            ->setIsNotifDiscussions(true)
            ->setIsNotifMessages(true)
            ->setIsRegistrationComplete(true)
            ->setPhone('+1234567890')
            ->setIsNotifOwnPostChanged(true)
            ->setToken('user1')
            ->setResetPasswordToken('x-reset-token')
            ->setResetPasswordAt(new \DateTime('-1 day'))
            ->setSlogan('User 1 Slogan')
            ->setBio('User 1 Bio')
            ->setFacebookId('xXxXxXxXxXx')
            ->setFacebookToken('yYyYyYyYyYy')
            ->setAvatarFileName(uniqid('', true).'.jpg');
        foreach (['district_la', 'district_sd', 'district_us'] as $item) {
            $user->addDistrict($this->getDistrict($item));
        }

        $this->encodePassword($user);
        $this->addReference('user_1', $user);
        $manager->persist($user);
        $manager->flush();

        $this->addReference('user_2', $this->generateUser('user2', null, 'district_nj'));

        $user3 = $this->generateUser(
            'user3',
            null,
            ['district_sd', 'district_us', 'district_nj']
        )
            ->setLatitude(40.781)
            ->setLongitude(-73.982);
        $this->addReference('user_3', $user3);

        $this->addReference('user_4', $this->generateUser('user4'));

        $this->addReference('followertest', $this->generateUser('followertest', null, ['district_la', 'district_sf', 'district_us']));
        $userFollowerTest1 = $this->generateUser('userfollowtest1', null, 'district_sd')
            ->setIsNotifOwnPostChanged(true);

        $this->addReference('userfollowtest1', $userFollowerTest1);
        $this->addReference('userfollowtest2', $this->generateUser('userfollowtest2'));
        $this->addReference('userfollowtest3', $this->generateUser('userfollowtest3'));
        $this->addReference('testuserbookmark1', $this->generateUser('testuserbookmark1'));

        $manager->flush();
    }

    /**
     * @param $username
     * @param \DateTime|null $birthDate
     * @param array|null|string $district
     * @return User
     */
    private function generateUser($username, $birthDate = null, $district = null): User
    {
        $birthDate = $birthDate ?: new \DateTime();

        $names = preg_split('/(\d+)/', $username, 2, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
        $user = new User();
        $user->setUsername($username)
            ->setFirstName($names[0])
            ->setLastName($names[1] ?? '')
            ->setEmail("$username@example.com")
            ->setPlainPassword($username)
            ->setBirth($birthDate)
            ->setDoNotDisturb(true)
            ->setIsNotifDiscussions(false)
            ->setIsNotifMessages(false)
            ->setIsRegistrationComplete(true)
            ->setIsNotifOwnPostChanged(false)
            ->setPhone('+'.mt_rand())
            ->setToken($username)
            ->setFacebookId('fb_'.$username);

        $district = (array)$district;
        foreach ($district as $item) {
            if ($district !== null) {
                $user->addDistrict($this->getDistrict($item));
            }
        }

        $this->encodePassword($user);

        $this->manager->persist($user);

        return $user;
    }

    private function encodePassword(User $user): void
    {
        /** @var PasswordEncoderInterface $encoder */
        $encoder = $this->container->get('security.encoder_factory')->getEncoder($user);
        $password = $encoder->encodePassword($user->getPlainPassword(), $user->getSalt());
        $user->setPassword($password);
    }

    /**
     * @param string $reference
     * @return District|mixed
     */
    private function getDistrict($reference)
    {
        return $this->getReference($reference);
    }

    public function getDependencies(): array
    {
        return [LoadDistrictData::class];
    }
}
