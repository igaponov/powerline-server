<?php

namespace Tests\Civix\CoreBundle\Model\Avatar;

use Civix\CoreBundle\Model\Avatar\FirstLetterDefaultAvatar;
use PHPUnit\Framework\TestCase;

class FirstLetterDefaultAvatarTest extends TestCase
{
    /**
     * @param $name
     * @param $letter
     * @dataProvider getNames
     */
    public function testLetter($name, $letter)
    {
        $avatar = new FirstLetterDefaultAvatar($name);
        $this->assertSame($letter, $avatar->getLetter());
    }

    public function getNames(): array
    {
        return [
            ['Test name', 'T'],
            ['another_name', 'A'],
            [123, 'O'],
            ['206', 'T'],
            ['3wrq', 'T'],
            ['4xxx', 'F'],
            [5, 'F'],
            [600, 'S'],
            ['7re', 'S'],
            ['8de', 'E'],
            ['9Q', 'N'],
            ['0O0O0', 'Z'],
        ];
    }
}
