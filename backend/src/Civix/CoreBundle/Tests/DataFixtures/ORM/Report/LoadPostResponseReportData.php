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
        /** @var Vote $vote1 */
        $vote1 = $this->getReference('post_answer_1');
        /** @var Vote $vote2 */
        $vote2 = $this->getReference('post_answer_2');
        /** @var Vote $vote3 */
        $vote3 = $this->getReference('post_answer_3');

        $report = new PostResponseReport(
            $vote1->getUser()->getId(),
            $vote1->getPost()->getId(),
            $vote1->getOptionTitle()
        );
        $manager->persist($report);

        $report = new PostResponseReport(
            $vote2->getUser()->getId(),
            $vote2->getPost()->getId(),
            $vote2->getOptionTitle()
        );
        $manager->persist($report);

        $report = new PostResponseReport(
            $vote3->getUser()->getId(),
            $vote3->getPost()->getId(),
            $vote3->getOptionTitle()
        );
        $manager->persist($report);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadPostVoteData::class];
    }
}