<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM\Group;

use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadGroupSectionData;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadQuestionGroupSectionData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var Question $poll1 */
        $poll1 = $this->getReference('group_question_1');
        /** @var Question $poll3 */
        $poll3 = $this->getReference('group_question_3');

        $section1 = $this->getReference('group_1_section_1');
        $section3 = $this->getReference('group_3_section_1');

        $poll1->addGroupSection($section1);
        $manager->persist($poll1);
        $poll3->addGroupSection($section3);
        $manager->persist($poll3);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadGroupQuestionData::class, LoadGroupSectionData::class];
    }
}