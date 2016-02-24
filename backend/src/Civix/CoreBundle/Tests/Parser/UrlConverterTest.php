<?php

namespace Civix\CoreBundle\Tests\Parser;

use Civix\CoreBundle\Parser\UrlConverter;

class UrlConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group parser
     */
    public function testIsURL()
    {
        $this->assertTrue(UrlConverter::isURL('http://example.com'), 'should support http protocol');
        $this->assertTrue(UrlConverter::isURL('https://example.com'), 'should support https protocol');
        $this->assertTrue(UrlConverter::isURL('www.example.co.uk'), 'should support www');
        $this->assertTrue(UrlConverter::isURL('www.example.co.uk?q=search'), 'should support www with get params');
        $this->assertFalse(UrlConverter::isURL('test'));
        $this->assertFalse(UrlConverter::isURL('httpexamplek'));
        $this->assertFalse(UrlConverter::isURL(''));
        $this->assertFalse(UrlConverter::isURL('test.test'));
    }

    /**
     * @group parser
     */
    public function testCreateLink()
    {
        $this->assertContains('<a href="http://www.example.com">www.example.com</a>',
            UrlConverter::createLink('www.example.com'),
            'should generate link with protocol');

        $this->assertContains('<a href="http://www.example.com">www.example.com</a>.',
            UrlConverter::createLink('www.example.com.'),
            'should split end of sentence');
        $this->assertContains('<a href="http://example.com">http://example.com</a>',
            UrlConverter::createLink('http://example.com'),
            'should generate link');
    }

    /**
     * @group parser
     */
    public function testCreateLinkLengthLimit()
    {
        $this->assertContains('Test with link for '.
            '<a href="http://www.example.very.long.link.com">www.example.very.lon...</a>',
            UrlConverter::convert('Test with link for www.example.very.long.link.com'),
            'should cut links');
    }

    /**
     * @group parser
     */
    public function testConvert()
    {
        $this->assertContains('Test with link for <a href="http://www.example.com">www.example.com</a>',
            UrlConverter::convert('Test with link for www.example.com'));
    }

    /**
     * @group parser
     */
    public function testConvertEscapeSpecialCharacters()
    {
        $this->assertContains('Test with link for &lt;script&gt; <a href="http://www.example.com">www.example.com</a>',
            UrlConverter::convert('Test with link for <script> www.example.com'));
        $this->assertContains('Test with link for '.
            '<a href="http://www.ex&lt;/a&gt;mple.com">www.ex&lt;/a&gt;mple.com</a>',
            UrlConverter::convert('Test with link for www.ex</a>mple.com'));
    }

    /**
     * @group parser
     */
    public function testWrapLinks()
    {
        $this->assertEquals(
            '<a href="www.powerli.ne" target="_blank">www.powerli.ne</a>',
            UrlConverter::wrapLinks('www.powerli.ne')
        );
        $this->assertEquals(
            '<a href="www.powerli.ne" target="_blank">www.powerli.ne</a>.',
            UrlConverter::wrapLinks('www.powerli.ne.')
        );
        $this->assertEquals(
            '<a href="http://powerli.ne" target="_blank">http://powerli.ne</a>',
            UrlConverter::wrapLinks('http://powerli.ne')
        );
        $this->assertEquals(
            '<a href="https://powerli.ne" target="_blank">https://powerli.ne</a>',
            UrlConverter::wrapLinks('https://powerli.ne')
        );
        $this->assertEquals(
            'check <a href="https://powerli.ne" target="_blank">https://powerli.ne</a>',
            UrlConverter::wrapLinks('check https://powerli.ne')
        );
        $this->assertEquals(
            '<a href="powerli.ne/petitions/322" target="_blank">powerli.ne/petitions/322</a> @powerline',
            UrlConverter::wrapLinks('powerli.ne/petitions/322 @powerline')
        );
        $this->assertEquals(
            '@mobile1.mobile should not be a link',
            UrlConverter::wrapLinks('@mobile1.mobile should not be a link')
        );
        $this->assertEquals(
            '@powerli.ne should not be a link',
            UrlConverter::wrapLinks('@powerli.ne should not be a link')
        );
        $this->assertEquals(
            '#powerli.ne should not be a link',
            UrlConverter::wrapLinks('#powerli.ne should not be a link')
        );
    }
}
