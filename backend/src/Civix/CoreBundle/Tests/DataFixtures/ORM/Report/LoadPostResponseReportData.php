<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM\Report;

use Civix\CoreBundle\Entity\Post\Vote;
use Civix\CoreBundle\Entity\Report\PostResponseReport;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostVoteData;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadPostResponseReportData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var Vote $vote */
        $vote = $this->getReference('post_answer_2');

        $report = new PostResponseReport(
            $vote->getUser()->getId(),
            $vote->getPost()->getId(),
            $vote->getOptionTitle()
        );
        $manager->persist($report);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadPostVoteData::class];
    }
}