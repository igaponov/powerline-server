<?php

namespace Tests\Civix\CoreBundle\Command;

use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Service\UserLocalGroupManager;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Liip\FunctionalTestBundle\Test\WebTestCase;

class LocalGroupsUpdateCommandTest extends WebTestCase
{
    public function testExecute(): void
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
        ])->getReferenceRepository();
        $users = array_map([$repository, 'getReference'], [
            'user_1',
            'user_2',
            'user_3',
            'user_4',
            'followertest',
            'userfollowtest1',
            'userfollowtest2',
            'userfollowtest3',
            'testuserbookmark1',
        ]);
        $manager = $this->getMockBuilder(UserLocalGroupManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['joinLocalGroups'])
            ->getMock();
        $manager->expects($this->exactly(count($users)))
            ->method('joinLocalGroups')
            ->withConsecutive(...array_map(function ($user) {
                return [$user];
            }, $users));
        $this->getContainer()->set('civix_core.service.user_local_group_manager', $manager);
        $output = array_reduce($users, function ($result, User $user) {
            return $result.'Processing user #'.$user->getId().PHP_EOL;
        }, '');
        $this->assertEquals($output, $this->runCommand('civix:local_groups:update', [], true));
    }
}
