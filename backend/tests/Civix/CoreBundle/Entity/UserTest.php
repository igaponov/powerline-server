<?php

namespace Tests\Civix\CoreBundle\Entity;

use Civix\CoreBundle\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\File;

class UserTest extends TestCase
{
    public function testSerialization()
    {
        $user = new User();
        $user->setAvatar(new File(__FILE__));
        $object = new \ReflectionObject($user);
        $property = $object->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($user, 27);
        $property->setAccessible(false);
        $serialized = serialize($user);
        $user = unserialize($serialized);
        $this->assertSame(27, $user->getId());
    }
}
