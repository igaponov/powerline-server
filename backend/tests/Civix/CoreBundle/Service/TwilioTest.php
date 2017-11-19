<?php

namespace Tests\Civix\CoreBundle\Service;

use Civix\CoreBundle\Service\Twilio;
use PHPUnit\Framework\TestCase;

class TwilioTest extends TestCase
{
    public function testGetChatToken()
    {
        $twilio = new Twilio('xxx', 'zzz', 'ccc', 'vvv');
        $token = $twilio->getChatToken('0111', 'e01');
        $this->assertNotEmpty($token->toJWT());
    }
}
