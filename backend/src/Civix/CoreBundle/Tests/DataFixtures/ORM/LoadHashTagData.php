<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\HashTag;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

class LoadHashTagData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $tag = new HashTag('#testHashTag');
        $manager->persist($tag);
        $this->addReference('tag_1', $tag);

        $tag = new HashTag('#powerlineHashTag');
        $manager->persist($tag);
        $this->addReference('tag_2', $tag);

        $tag = new HashTag('#anotherHashTag');
        $manager->persist($tag);
        $this->addReference('tag_3', $tag);

        $manager->flush();
    }
}
