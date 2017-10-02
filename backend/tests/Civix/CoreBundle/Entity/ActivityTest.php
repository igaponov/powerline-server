<?php

namespace Tests\Civix\CoreBundle\Entity;

use Civix\CoreBundle\Entity\Activities\LeaderEvent;
use Civix\CoreBundle\Entity\Activities\PaymentRequest;
use Civix\CoreBundle\Entity\Activities\Petition;
use Civix\CoreBundle\Entity\Activities\Post;
use Civix\CoreBundle\Entity\Activities\UserPetition;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Entity\Superuser;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Serializer\Type\GroupOwnerData;
use Civix\CoreBundle\Serializer\Type\OwnerData;
use Civix\CoreBundle\Serializer\Type\RepresentativeOwnerData;
use Civix\CoreBundle\Serializer\Type\UserOwnerData;
use PHPUnit\Framework\TestCase;

class ActivityTest extends TestCase
{
    public function testGetUserOwnerData()
    {
        $user = new User();
        $activity = new Post();
        $activity->setUser($user);
        $data = $activity->getOwnerData();
        $this->assertInstanceOf(UserOwnerData::class, $data);
    }

    public function testGetGroupOwnerData()
    {
        $group = new Group();
        $activity = new Petition();
        $activity->setGroup($group);
        $data = $activity->getOwnerData();
        $this->assertInstanceOf(GroupOwnerData::class, $data);
    }

    public function testGetRepresentativeOwnerData()
    {
        $representative = new Representative();
        $representative->setUser(new User());
        $activity = new UserPetition();
        $activity->setRepresentative($representative);
        $data = $activity->getOwnerData();
        $this->assertInstanceOf(RepresentativeOwnerData::class, $data);
    }

    public function testGetOwnerData()
    {
        $superuser = new Superuser();
        $activity = new LeaderEvent();
        $activity->setSuperuser($superuser);
        $data = $activity->getOwnerData();
        $this->assertInstanceOf(OwnerData::class, $data);
    }

    public function testGetEmptyOwnerData()
    {
        $activity = new PaymentRequest();
        $data = $activity->getOwnerData();
        $this->assertInstanceOf(OwnerData::class, $data);
    }
}
