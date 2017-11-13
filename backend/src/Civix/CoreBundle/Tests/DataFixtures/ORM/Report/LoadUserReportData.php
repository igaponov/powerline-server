<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM\Report;

use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Entity\Report\UserReport;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadRepresentativeData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserFollowerData;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadUserReportData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var User $user1 */
        $user1 = $this->getReference('user_1');
        /** @var User $user3 */
        $user3 = $this->getReference('user_3');
        /** @var User $user4 */
        $user4 = $this->getReference('user_4');
        /** @var Representative $representativeBo */
        $representativeBo = $this->getReference('cicero_representative_bo');
        /** @var Representative $representativeJb */
        $representativeJb = $this->getReference('cicero_representative_jb');
        /** @var Representative $representativeRm */
        $representativeRm = $this->getReference('cicero_representative_rm');
        /** @var Representative $representativeKg */
        $representativeKg = $this->getReference('cicero_representative_kg');

        $report = new UserReport($user1->getId(), 0, [$representativeKg->getFullName()], 'US', 'NY', 'New York', ['United States', 'New York'], 10);
        $manager->persist($report);

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
            LoadRepresentativeData::class,
        ];
    }
}