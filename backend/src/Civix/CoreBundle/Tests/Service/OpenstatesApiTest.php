<?php

namespace Civix\CoreBundle\Tests\Service;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class OpenstatesApiTest extends WebTestCase
{
    /**
     * Test method getRepresentativeByName.
     *
     * @group openstates
     */
    public function testGetRepresentativeByName()
    {
        $openstatesMock = $this->getMock('Civix\CoreBundle\Service\OpenstatesApi',
            array('getResponse'),
            array(),
            '',
            false
        );
        $openstatesMock->expects($this->any())
           ->method('getResponse')
           ->will($this->returnValue(json_decode('[{"leg_id":12}]')));
        $legId = $openstatesMock->getRepresentativeByName('firstName', 'lastName');
        $this->assertEquals(12, $legId, 'Should be return correct id');

        $openstatesMock = $this->getMock('Civix\CoreBundle\Service\OpenstatesApi',
            array('getResponse'),
            array(),
            '',
            false
        );
        $openstatesMock->expects($this->any())
            ->method('getResponse')
            ->will($this->returnValue(json_decode('[]')));
        $legId = $openstatesMock->getRepresentativeByName('firstName', 'lastName');
        $this->assertFalse($legId, 'Should be return false for empty array');

        $openstatesMock = $this->getMock('Civix\CoreBundle\Service\OpenstatesApi',
            array('getResponse'),
            array(),
            '',
            false
        );
        $openstatesMock->expects($this->any())
           ->method('getResponse')
           ->will($this->returnValue(json_decode('')));
        $legId = $openstatesMock->getRepresentativeByName('firstName', 'lastName');
        $this->assertFalse($legId, 'Should be return false for empty string');

        $openstatesMock = $this->getMock('Civix\CoreBundle\Service\OpenstatesApi',
            array('getResponse'),
            array(),
            '',
            false
        );
        $openstatesMock->expects($this->any())
           ->method('getResponse')
           ->will($this->returnValue(json_decode(null)));
        $legId = $openstatesMock->getRepresentativeByName('firstName', 'lastName');
        $this->assertFalse($legId, 'Should be return false for empty string');
    }
}
