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
        /** @var Signature $signature */
        $signature = $this->getReference('petition_answer_2');

        $report = new PetitionResponseReport(
            $signature->getUser()->getId(),
            $signature->getPetition()->getId()
        );
        $manager->persist($report);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadUserPetitionSignatureData::class];
    }
}