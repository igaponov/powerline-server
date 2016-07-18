<?php
namespace Civix\CoreBundle\Tests\Entity\Activities;

use Civix\CoreBundle\Entity\Activities\MicroPetition;
use Civix\CoreBundle\Entity\Activities\Petition;
use Civix\CoreBundle\Entity\Micropetitions\Metadata;

class MicroPetitionTest extends \PHPUnit_Framework_TestCase
{
    public function testPetitionClassNameConflict()
    {
        new Petition(); // conflict between Activities\Petition and Micropetitions\Petition
        $microPetition = new MicroPetition();
        $petition = new \Civix\CoreBundle\Entity\Micropetitions\Petition();
        $petition->setMetadata(new Metadata());
        $microPetition->setPetition($petition);
        $this->assertInstanceOf(Metadata::class, $microPetition->getMetadata());
    }

    public function testGetEntity()
    {
        $microPetition = new MicroPetition();
        $petition = new \Civix\CoreBundle\Entity\Micropetitions\Petition();
        $petition->setType(\Civix\CoreBundle\Entity\Micropetitions\Petition::TYPE_LONG_PETITION);
        $microPetition->setPetition($petition);
        $this->assertEquals([
            'type' => 'micro-petition:long-petition',
            'id' => null,
            'group_id' => null,
        ], $microPetition->getEntity());
    }
}