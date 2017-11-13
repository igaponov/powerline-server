<?php

namespace Tests\Civix\CoreBundle\Repository\Report;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\Report\UserReport;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadKarmaData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Report\LoadUserReportData;

class UserReportRepositoryTest extends WebTestCase
{
    public function testUpdateUserReportKarma()
    {
        $referenceRepository = $this->loadFixtures([
            LoadKarmaData::class,
            LoadUserReportData::class,
        ])->getReferenceRepository();
        /** @var User $user */
        $user = $referenceRepository->getReference('user_1');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repository = $em->getRepository(UserReport::class);
        $repository->updateUserReportKarma($user);
        $report = $repository->getUserReport($user);
        $this->assertEquals(63, $report[0]['karma']);
    }
}
