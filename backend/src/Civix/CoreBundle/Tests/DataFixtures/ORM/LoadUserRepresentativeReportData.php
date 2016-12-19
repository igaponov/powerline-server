<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserRepresentativeReport;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadUserRepresentativeReportData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var User $user */
        $user = $this->getReference('user_3');
        $bo = $this->getReference('cicero_representative_bo');
        $jb = $this->getReference('cicero_representative_jb');
        $kg = $this->getReference('cicero_representative_kg');
        $eh = $this->getReference('cicero_representative_eh');
        $rm = $this->getReference('cicero_representative_rm');

        $representativeReport = new UserRepresentativeReport($user);
        $representativeReport
            ->setPresident($bo->getFullName())
            ->setVicePresident($jb->getFullName())
            ->setSenator1($rm->getFullName())
            ->setSenator2($kg->getFullName())
            ->setCongressman($eh->getFullName());
        $this->addReference('user_representative_report_3', $representativeReport);
        $manager->persist($representativeReport);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadUserData::class, LoadCiceroRepresentativeData::class];
    }
}
