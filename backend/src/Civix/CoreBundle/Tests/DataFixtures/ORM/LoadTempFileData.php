<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\TempFile;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

class LoadTempFileData extends AbstractFixture
{
    public function load(ObjectManager $manager)
    {
        $file = new TempFile(
            serialize([['x' => 2, 'y' => 5], ['x' => 7, 'y' => 90]]),
            new \DateTime('+2 minutes'),
            'text/csv',
            'test_file_name.csv'
        );
        $manager->persist($file);
        $this->addReference('file_1', $file);

        $file = new TempFile(
            serialize([['i' => 100, 'j' => 500]]),
            new \DateTime('-1 second'),
            'text/csv'
        );
        $manager->persist($file);
        $this->addReference('file_2', $file);

        $manager->flush();
    }
}
