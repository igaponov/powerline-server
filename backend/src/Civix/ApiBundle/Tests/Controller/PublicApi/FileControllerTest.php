<?php

namespace Civix\ApiBundle\Tests\Controller\PublicApi;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\TempFile;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadTempFileData;

class FileControllerTest extends WebTestCase
{
    const API_ENDPOINT = '/api-public/files/';

    public function testGetFile()
    {
        $repository = $this->loadFixtures([
            LoadTempFileData::class,
        ])->getReferenceRepository();
        /** @var TempFile $file */
        $file = $repository->getReference('file_1');
        $client = $this->makeClient();
        $client->request('GET', self::API_ENDPOINT.$file->getId());
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $this->assertTrue($response->headers->contains('content-disposition', sprintf('attachment; filename="%s"', $file->getFilename())));
        $this->assertContains($file->getMimeType(), $response->headers->get('content-type', null, true));
        $this->assertEquals("x,y\n2,5\n7,90\n", $response->getContent());
    }
}