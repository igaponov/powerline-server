<?php

namespace Tests\Civix\CoreBundle\Command;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Superuser;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadSuperuserData;

class SuperuserPasswordCommandTest extends WebTestCase
{
    public function testExecute()
    {
        $repository = $this->loadFixtures([
            LoadSuperuserData::class,
        ])->getReferenceRepository();
        /** @var Superuser $user */
        $user = $repository->getReference('superuser-admin');
        $oldPassword = $user->getPassword();
        $this->runCommand('civix:superuser:password', ['username' => 'admin']);
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $em->refresh($user);
        $this->assertNotEquals($oldPassword, $user->getPassword());
    }
}
