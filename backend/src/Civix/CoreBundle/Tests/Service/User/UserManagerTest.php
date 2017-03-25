<?php
namespace Civix\CoreBundle\Tests\Service\User;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Report\UserReport;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Service\CiceroApi;
use Civix\CoreBundle\Service\User\UserManager;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadCiceroRepresentativeData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserFollowerData;
use Doctrine\ORM\EntityManager;

class UserManagerTest extends WebTestCase
{
    public function testUpdateDistrictsIds()
    {
        $repository = $this->loadFixtures([
            LoadUserFollowerData::class,
            LoadCiceroRepresentativeData::class,
        ])->getReferenceRepository();
        /** @var \PHPUnit_Framework_MockObject_MockObject|CiceroApi $cicero */
        $cicero = $this->getMockBuilder(CiceroApi::class)
            ->setMethods(['getRepresentativesByLocation'])
            ->disableOriginalConstructor()
            ->getMock();
        $bo = $repository->getReference('cicero_representative_bo');
        $jb = $repository->getReference('cicero_representative_jb');
        $kg = $repository->getReference('cicero_representative_kg');
        $eh = $repository->getReference('cicero_representative_eh');
        $rm = $repository->getReference('cicero_representative_rm');
        $cicero->expects($this->once())
            ->method('getRepresentativesByLocation')
            ->willReturn([$jb, $eh, $kg, $bo, $rm]);
        /** @var EntityManager $em */
        $em = $this->getContainer()
            ->get('doctrine')
            ->getManager();
        $manager = new UserManager(
            $em,
            $cicero,
            $this->getContainer()->get('civix_core.group_manager'),
            $this->getContainer()->get('civix_core.crop_image'),
            $this->getContainer()->get('event_dispatcher'),
            '/'
        );
        /** @var User $user */
        $user = $repository->getReference('user_2');
        $manager->updateDistrictsIds($user);
        $em->flush();
        $result = $em->getRepository(UserReport::class)
            ->getUserReport($user);
        $this->assertEquals($user->getId(), $result[0]['user']);
        $this->assertEquals(1, $result[0]['followers']);
        $this->assertEquals([
            "Vice President Joseph Biden",
            "Congressman Eleanor Holmes",
            "Senator Kirsten Gillibrand",
            "President Barack Obama",
            "Senator Robert Menendez",
        ], $result[0]['representatives']);
        $this->assertEquals([
            'United States',
            'New Jersey',
        ], $result[0]['districts']);
    }
}