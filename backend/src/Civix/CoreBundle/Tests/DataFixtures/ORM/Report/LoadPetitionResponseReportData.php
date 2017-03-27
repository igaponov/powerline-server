<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM\Report;

use Civix\CoreBundle\Entity\Report\PetitionResponseReport;
use Civix\CoreBundle\Entity\UserPetition\Signature;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserPetitionSignatureData;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadPetitionResponseReportData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var Signature $signature1 */
        $signature1 = $this->getReference('petition_answer_1');
        /** @var Signature $signature2 */
        $signature2 = $this->getReference('petition_answer_2');
        /** @var Signature $signature3 */
        $signature3 = $this->getReference('petition_answer_3');

        $report = new PetitionResponseReport(
            $signature1->getUser()->getId(),
            $signature1->getPetition()->getId()
        );
        $manager->persist($report);

        $report = new PetitionResponseReport(
            $signature2->getUser()->getId(),
            $signature2->getPetition()->getId()
        );
        $manager->persist($report);

        $report = new PetitionResponseReport(
            $signature3->getUser()->getId(),
            $signature3->getPetition()->getId()
        );
        $manager->persist($report);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadUserPetitionSignatureData::class];
    }
}