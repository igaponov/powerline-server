<?php

namespace Tests\Civix\Component\ContentConverter\Source;

use Civix\Component\ContentConverter\Source\Url;
use PHPUnit\Framework\TestCase;

class UrlTest extends TestCase
{
    public function testIgnore()
    {
        $url = 'http://imgix.net/example.jpg';
        $source = new Url(['imgix.net']);
        $this->assertTrue($source->isSupported($url));
        $this->assertNull($source->convert($url));
    }

    public function testEmptyUrl()
    {
        $source = new Url();
        $this->assertNull($source->convert(''));
    }

    public function testInvalidUrl()
    {
        $source = new Url();
        $this->assertNull($source->convert('qwerty'));
    }
}