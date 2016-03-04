<?php
namespace Civix\CoreBundle\Tests\Service;

use Civix\CoreBundle\Entity\Micropetitions\Metadata;
use Civix\CoreBundle\Service\HTMLMetadataParser;

class HTMLMetadataParserTest extends \PHPUnit_Framework_TestCase
{
    public function testParseReturnsMetadata()
    {
        $html = <<<HTML
<html>
<head>
<title>some title</title>
<meta name="og:description" content="some Description">
<meta name="twitter:image" content="Some image">
</head>
<body></body>
</html>
HTML;

        $parser = new HTMLMetadataParser();
        $metadata = $parser->parse($html);
        $this->assertInstanceOf(Metadata::class, $metadata);
        $this->assertSame('some title', $metadata->getTitle());
        $this->assertSame('some Description', $metadata->getDescription());
        $this->assertSame('Some image', $metadata->getImage());
    }
}