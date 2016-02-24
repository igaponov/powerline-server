<?php

namespace Civix\CoreBundle\Tests\Parser;

use Civix\CoreBundle\Parser\Tags;

class TagsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group parser
     */
    public function testParseHashTags()
    {
        $this->assertContains('#hash', Tags::parseHashTags('test #hash test')['parsed']);
        $this->assertContains('#hash', Tags::parseHashTags('#hash')['parsed'], 'full');
        $this->assertContains('#hash', Tags::parseHashTags('test #hash')['parsed'], 'end');
        $this->assertContains('#hash', Tags::parseHashTags('#hash test')['parsed'], 'start');
        $this->assertContains('#hash', Tags::parseHashTags('test #hash, test')['parsed'], 'separator ","');
        $this->assertContains('#hash', Tags::parseHashTags('test #hash! test')['parsed'], 'separator "!"');
        $this->assertContains('#hash', Tags::parseHashTags('test #hash?')['parsed'], 'separator "?"');
        $this->assertContains('#hash2', Tags::parseHashTags('test #hash1 test #hash2.')['parsed'], 'multiple');
        $this->assertContains('#hash-tag', Tags::parseHashTags('test #hash-Tag test')['parsed'], 'lowercase');
        $this->assertCount(3, Tags::parseHashTags('test #hash1 test #hash2 #hash5')['parsed'], 'multiple - count');
        $this->assertCount(1, Tags::parseHashTags('test #hash test #hash')['parsed'], 'repeated as single');
        $this->assertCount(0, Tags::parseHashTags('test hash1 tst hash2 hash5')['parsed'], 'not found, empty array');
        $this->assertCount(0, Tags::parseHashTags('www.example.com#test www.example.com/#test')['parsed'], 'not found when link');
        $this->assertContains('#hash-Tag', Tags::parseHashTags('test #hash-Tag test')['original'], 'original');
    }

    /**
     * @group parser
     */
    public function testParseMentionTags()
    {
        $this->assertContains('tom',    Tags::parseMentionTags('@tom'));
        $this->assertContains('tom',    Tags::parseMentionTags('What about @tom?'));
        $this->assertContains('tom24',  Tags::parseMentionTags('My username is @tom24'));
        $this->assertContains('tom_24', Tags::parseMentionTags('@tom_24'));
        $this->assertContains('tom-24', Tags::parseMentionTags('@alice @tom-24'));
        $this->assertContains('alice',  Tags::parseMentionTags('@alice @tom-24'));
        $this->assertContains('1.JesseChen',  Tags::parseMentionTags('@1.JesseChen'));
        $this->assertContains('alice',  Tags::parseMentionTags('@alice.'));
        $this->assertContains('mobile1.mobile',  Tags::parseMentionTags('@mobile1.mobile '));
    }

    /**
     * @group parser
     */
    public function testReplaceMentionTags()
    {
        $replacements = [
            '@tom' => '<a>@tom</a>',
            '@alice21' => '<a>@alice21</a>',
            '@alice' => '<a>@alice</a>',
            '@alice2' => '<a>@alice2</a>',
            '@1.JesseChen' => '<a>@1.JesseChen</a>',
            '@mobile1.mobile' => '<a>@mobile1.mobile</a>',
        ];

        $this->assertEquals(
            '<a>@tom</a>.',
            Tags::replaceMentionTags('@tom.', $replacements)
        );
        $this->assertEquals(
            '<a>@alice21</a> is not <a>@alice2</a>',
            Tags::replaceMentionTags('@alice21 is not @alice2', $replacements)
        );
        $this->assertEquals(
            '@unregistered cannot be replaced',
            Tags::replaceMentionTags('@unregistered cannot be replaced', $replacements)
        );
        $this->assertEquals(
            '<a>@tom</a>',
            Tags::replaceMentionTags('<a>@tom</a>', $replacements)
        );
        $this->assertEquals(
            '<a>@1.JesseChen</a>',
            Tags::replaceMentionTags('@1.JesseChen', $replacements)
        );
        $this->assertEquals(
            '<a>@mobile1.mobile</a> ',
            Tags::replaceMentionTags('@mobile1.mobile ', $replacements)
        );
    }

    /**
     * @group parser
     */
    public function testWrapHashTags()
    {
        $this->assertEquals(
            '<a data-hashtag="#powerline">#powerline</a>',
            Tags::wrapHashTags('#powerline')
        );
        $this->assertEquals(
            '<a data-hashtag="#powerline">#powerline</a> <a data-hashtag="#politics">#politics</a>',
            Tags::wrapHashTags('#powerline #politics')
        );
        $this->assertEquals(
            '<a data-hashtag="#powerline">#powerline</a>.',
            Tags::wrapHashTags('#powerline.')
        );
    }
}
