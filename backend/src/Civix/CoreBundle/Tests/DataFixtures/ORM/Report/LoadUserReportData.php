<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM\Report;

use Civix\CoreBundle\Entity\CiceroRepresentative;
use Civix\CoreBundle\Entity\Report\UserReport;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadCiceroRepresentativeData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserFollowerData;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadUserReportData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var User $user3 */
        $user3 = $this->getReference('user_3');
        /** @var User $user4 */
        $user4 = $this->getReference('user_4');
        /** @var CiceroRepresentative $representativeBo */
        $representativeBo = $this->getReference('cicero_representative_bo');
        /** @var CiceroRepresentative $representativeJb */
        $representativeJb = $this->getReference('cicero_representative_jb');
        /** @var CiceroRepresentative $representativeRm */
        $representativeRm = $this->getReference('cicero_representative_rm');

        $report = new UserReport($user3->getId(), 0, [$representativeRm->getFullName()], 'US', 'NY', 'New York', ['United States', 'New York'], 20);
        $manager->persist($report);

        $report = new UserReport($user4->getId(), 1, [
            $representativeBo->getFullName(),
            $representativeJb->getFullName()
        ]);
        $manager->persist($report);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            LoadUserData::class,
            LoadUserFollowerData::class,
            LoadCiceroRepresentativeData::class,
        ];
    }
}