<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Poll\EducationalContext;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadGroupQuestionData;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class LoadEducationalContextData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $poll1 = $this->getReference('group_question_1');
        $poll3 = $this->getReference('group_question_3');

        $context = new EducationalContext();
        $context->setQuestion($poll1)
            ->setType(EducationalContext::TEXT_TYPE)
            ->setText('Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo.');
        $manager->persist($context);
        $this->addReference('educational_context_1', $context);

        $context = new EducationalContext();
        $context->setQuestion($poll1)
            ->setType(EducationalContext::VIDEO_TYPE)
            ->setText('<iframe title="YouTube video player" class="youtube-player" type="text/html" width="640" height="390" src="http://www.youtube.com/embed/W-Q7RMpINVo" frameborder="0" allowFullScreen></iframe>');
        $manager->persist($context);
        $this->addReference('educational_context_2', $context);

        $context = new EducationalContext();
        $context->setQuestion($poll1)
            ->setType(EducationalContext::IMAGE_TYPE)
            ->setImage(new UploadedFile(__DIR__.'/../data/image.png', 'image.png'));
        $manager->persist($context);
        $this->addReference('educational_context_3', $context);

        $context = new EducationalContext();
        $context->setQuestion($poll3)
            ->setType(EducationalContext::TEXT_TYPE)
            ->setText('Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt.');
        $manager->persist($context);
        $this->addReference('educational_context_4', $context);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadGroupQuestionData::class];
    }
}
