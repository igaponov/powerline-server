<?php

namespace Civix\CoreBundle\Tests\Service;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Civix\CoreBundle\Entity\User;

class UserManagerTest extends WebTestCase
{
    /**
     * @group forgot
     */
    public function testCheckResetInterval()
    {
        $userManager = $this->getMockBuilder('Civix\CoreBundle\Service\User\UserManager')
            ->disableOriginalConstructor()
            ->setMethods(array('updateDistrictsIds'))
            ->getMock();

        $user = new User();

        $user->setResetPasswordAt(null);
        $this->assertTrue($userManager->checkResetInterval($user), 'True if reset password date is empty');

        $user->setResetPasswordAt(new \DateTime());
        $this->assertFalse($userManager->checkResetInterval($user), 'False if reset password date is current date');

        $yesterday = new \DateTime();
        $yesterday->sub(new \DateInterval('PT24H'));
        $user->setResetPasswordAt($yesterday);
        $this->assertTrue($userManager->checkResetInterval($user), 'True if reset password date is yesterday');

        $checkDate = new \DateTime();
        $checkDate->sub(new \DateInterval('PT23H'));
        $user->setResetPasswordAt($checkDate);
        $this->assertFalse($userManager->checkResetInterval($user), 'False if reset password date is 23 hours ago');
    }
}
