<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM\Report;

use Civix\CoreBundle\Entity\Poll\Answer;
use Civix\CoreBundle\Entity\Report\PollResponseReport;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadQuestionAnswerData;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadPollResponseReportData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var Answer $answer1 */
        $answer1 = $this->getReference('question_answer_1');
        /** @var Answer $answer2 */
        $answer2 = $this->getReference('question_answer_2');
        /** @var Answer $answer3 */
        $answer3 = $this->getReference('question_answer_3');
        /** @var Answer $answer4 */
        $answer4 = $this->getReference('question_answer_4');

        $report = new PollResponseReport(
            $answer1->getUser()->getId(),
            $answer1->getQuestion()->getId(),
            $answer1->getQuestion()->getGroup()->getId(),
            $answer1->getQuestion()->getSubject(),
            $answer1->getOption()->getValue(),
            $answer1->getComment(),
            $answer1->getPrivacy()
        );
        $manager->persist($report);

        $report = new PollResponseReport(
            $answer2->getUser()->getId(),
            $answer2->getQuestion()->getId(),
            $answer2->getQuestion()->getGroup()->getId(),
            $answer2->getQuestion()->getSubject(),
            $answer2->getOption()->getValue(),
            $answer2->getComment(),
            $answer2->getPrivacy()
        );
        $manager->persist($report);

        $report = new PollResponseReport(
            $answer3->getUser()->getId(),
            $answer3->getQuestion()->getId(),
            $answer3->getQuestion()->getGroup()->getId(),
            $answer3->getQuestion()->getSubject(),
            $answer3->getOption()->getValue(),
            $answer3->getComment(),
            $answer3->getPrivacy()
        );
        $manager->persist($report);

        $report = new PollResponseReport(
            $answer4->getUser()->getId(),
            $answer4->getQuestion()->getId(),
            $answer4->getQuestion()->getGroup()->getId(),
            $answer4->getQuestion()->getSubject(),
            $answer4->getOption()->getValue(),
            $answer4->getComment(),
            $answer4->getPrivacy()
        );
        $manager->persist($report);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadQuestionAnswerData::class];
    }
}