<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM\Issue;

use Civix\CoreBundle\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use libphonenumber\PhoneNumber;

/**
 * https://github.com/PowerlineApp/powerline-mobile/issues/533
 */
class PM533 extends AbstractFixture
{
    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setUsername('user1_dup')
            ->setFirstName('User')
            ->setLastName('Duplicate')
            ->setEmail('user1@example.com')
            ->setPassword('pass')
            ->setPlainPassword('user1')
            ->setBirth(new \DateTime('-30 years'))
            ->setDoNotDisturb(false)
            ->setIsNotifDiscussions(true)
            ->setIsNotifMessages(true)
            ->setIsRegistrationComplete(true)
            ->setPhone((new PhoneNumber())->setCountryCode(1)->setNationalNumber('234567891'))
            ->setIsNotifOwnPostChanged(true)
            ->setToken('user1_dup')
            ->setResetPasswordToken('x-reset-token')
            ->setResetPasswordAt(new \DateTime('-1 day'))
            ->setSlogan('User 1 Slogan')
            ->setBio('User 1 Bio')
            ->setFacebookId('xXxXxXxXxXxX')
            ->setFacebookToken('yYyYyYyYyYyY')
            ->setAvatarFileName(uniqid('', true).'.jpg');

        $this->addReference('user_1_dup', $user);
        $manager->persist($user);
        $manager->flush();
    }
}