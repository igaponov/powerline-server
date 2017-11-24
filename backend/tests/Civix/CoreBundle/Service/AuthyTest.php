<?php

namespace Tests\Civix\CoreBundle\Service;

use Civix\CoreBundle\Service\Authy;
use GuzzleHttp\Command\Guzzle\GuzzleClient;
use GuzzleHttp\Command\Result;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;
use PHPUnit\Framework\TestCase;

class AuthyTest extends TestCase
{
    public function testStartVerification()
    {
        $phoneNumber = (new PhoneNumber())
            ->setCountryCode(1)
            ->setNationalNumber('111111111');
        $result = new Result([]);
        $client = $this->getGuzzleClientMock();
        $client->expects($this->once())
            ->method('__call')
            ->with('startVerification', [[
                'country_code' => 1,
                'phone_number' => '111111111',
                'via' => 'sms',
            ]])
            ->willReturn($result);
        $util = $this->getMockBuilder(PhoneNumberUtil::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNumberType'])
            ->getMock();
        $util->expects($this->once())
            ->method('getNumberType')
            ->with($phoneNumber)
            ->willReturn(PhoneNumberType::MOBILE);
        $authy = new Authy($client, $util);
        $this->assertSame($result, $authy->startVerification($phoneNumber));
    }

    public function testCheckVerification()
    {
        $result = new Result([]);
        $client = $this->getGuzzleClientMock();
        $client->expects($this->once())
            ->method('__call')
            ->with('checkVerification', [[
                'country_code' => 1,
                'phone_number' => '111111111',
                'verification_code' => '246135',
            ]])
            ->willReturn($result);
        $authy = new Authy($client);
        $phoneNumber = (new PhoneNumber())
            ->setCountryCode(1)
            ->setNationalNumber('111111111');
        $code = '246135';
        $this->assertSame($result, $authy->checkVerification($phoneNumber, $code));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|GuzzleClient
     */
    private function getGuzzleClientMock(): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->getMockBuilder(GuzzleClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['__call'])
            ->getMock();
    }
}
