<?php

namespace Civix\CoreBundle\Tests\Entity\Poll;

use Civix\CoreBundle\Entity\Poll\Answer;
use Civix\CoreBundle\Entity\Poll\Option;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\Poll\Question\Representative;

class QuestionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group question
     */
    public function testGetMaxAnswers()
    {
        $question = $this->getNewQuestion();

        $this->assertEquals(0, $question->getMaxAnswers());

        $question = $this->getNewQuestion();
        $question->addOption($this->createNewOption($question, 2));
        $question->addOption($this->createNewOption($question, 10));

        $this->assertEquals(10, $question->getMaxAnswers());

        $question = $this->getNewQuestion();
        $question->addOption($this->createNewOption($question, 10));
        $question->addOption($this->createNewOption($question, 10));

        $this->assertEquals(10, $question->getMaxAnswers());

        $question = $this->getNewQuestion();
        $question->addOption($this->createNewOption($question, 0));
        $question->addOption($this->createNewOption($question, 0));

        $this->assertEquals(0, $question->getMaxAnswers());
    }
    /**
     * @group question
     */
    public function testGetStatistic()
    {
        $question = $this->getNewQuestion();
        $question->addOption($this->createNewOption($question, 10));
        $question->addOption($this->createNewOption($question, 10));

        $statistic = $question->getStatistic();

        $this->assertCount(2, $statistic);
        $this->assertInstanceOf('Civix\CoreBundle\Entity\Poll\Option', $statistic[0]['option']);
        $this->assertEquals(50, $statistic[0]['percent_answer']);
        $this->assertEquals(100, $statistic[0]['percent_width']);

        $question = $this->getNewQuestion();
        $question->addOption($this->createNewOption($question, 1));
        $question->addOption($this->createNewOption($question, 2));
        $question->addOption($this->createNewOption($question, 3));
        $question->addOption($this->createNewOption($question, 4));

        $statistic = $question->getStatistic();

        $this->assertCount(4, $statistic);
        $this->assertEquals(25, $statistic[0]['percent_width']);
        $this->assertEquals(50, $statistic[1]['percent_width']);
        $this->assertEquals(75, $statistic[2]['percent_width']);
        $this->assertEquals(100, $statistic[3]['percent_width']);
        $this->assertEquals(10, $statistic[0]['percent_answer']);
        $this->assertEquals(20, $statistic[1]['percent_answer']);
        $this->assertEquals(30, $statistic[2]['percent_answer']);
        $this->assertEquals(40, $statistic[3]['percent_answer']);

        $question = $this->getNewQuestion();
        $this->assertEmpty($question->getStatistic());
    }
    /**
     * @group question
     */
    public function testGetAnswersCount()
    {
        $question = $this->getNewQuestion();
        $question->addOption($this->createNewOption($question, 10));
        $question->addOption($this->createNewOption($question, 15));

        $this->assertEquals(25, $question->getAnswers()->count());
    }

    /**
     * @return Representative
     */
    protected function getNewQuestion()
    {
        return new Representative();
    }
    /**
     * @return Answer
     */
    protected function getNewAnswer()
    {
        return new Answer();
    }
    /**
     * @param Question $question
     * @param int $answersCount
     *
     * @return Option
     */
    protected function createNewOption(Question $question, $answersCount = 0)
    {
        $option = new Option();

        for ($i = 0; $i < $answersCount; ++$i) {
            $answer = $this->getNewAnswer();

            $option->addAnswer($answer);
            $question->addAnswer($answer);
        }

        return $option;
    }
}
