<?php
namespace Civix\CoreBundle\Tests\Service\User;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserRepresentativeReport;
use Civix\CoreBundle\Service\CiceroApi;
use Civix\CoreBundle\Service\User\UserManager;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadCiceroRepresentativeData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Doctrine\ORM\EntityManager;

class UserManagerTest extends WebTestCase
{
    public function testUpdateDistrictsIds()
    {
        $repository = $this->loadFixtures([
            LoadUserData::class,
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
        $user = $repository->getReference('user_1');
        $manager->updateDistrictsIds($user);
        $em->flush();
        /** @var UserRepresentativeReport $report */
        $report = $em->getRepository(UserRepresentativeReport::class)->findOneBy(['user' => $user]);
        $this->assertSame($bo->getFullName(), $report->getPresident());
        $this->assertSame($jb->getFullName(), $report->getVicePresident());
        $this->assertSame($kg->getFullName(), $report->getSenator1());
        $this->assertSame($rm->getFullName(), $report->getSenator2());
        $this->assertSame($eh->getFullName(), $report->getCongressman());
    }
}