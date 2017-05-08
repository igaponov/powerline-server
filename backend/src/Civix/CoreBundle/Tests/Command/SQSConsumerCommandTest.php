<?php

namespace Civix\CoreBundle\Tests\Command;

use Aws\CommandInterface;
use Aws\Credentials\Credentials;
use Aws\MockHandler;
use Aws\Result;
use Aws\Sns\MessageValidator;
use Aws\Sqs\SqsClient;
use Faker\Factory;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Component\Console\Output\StreamOutput;

class SQSConsumerCommandTest extends WebTestCase
{
    public function testExecuteWithNoMessagesInQueue()
    {
        $container = $this->getContainer();
        $queue = 'https://sqs.us-east-1.amazonaws.com/1234567890/bounces';
        $count = 5;
        $client = $this->getClientMock([
            function(CommandInterface $command) use ($queue, $count) {
                $this->assertEquals($queue, $command->offsetGet('QueueUrl'));
                $this->assertEquals($count, $command->offsetGet('MaxNumberOfMessages'));

                return new Result();
            }
        ]);
        $container->set('aws.sqs', $client);
        $output = $this->runCommand(
            'civix:sqs:consumer',
            [
                'queue' => $queue,
                'handler' => 'notification_handler',
                '--count' => $count,
            ],
            true
        );

        $this->assertContains("No messages in queue", $output);
    }

    public function testExecute()
    {
        $faker = Factory::create();
        $this->loadFixtures([]);
        $container = $this->getContainer();
        $validator = $this->getMockBuilder(MessageValidator::class)
            ->setMethods(['isValid'])
            ->getMock();
        $validator->expects($this->exactly(5))
            ->method('isValid')
            ->willReturn(true);
        $container->set('aws.message_validator', $validator);
        $queue = 'https://sqs.us-east-1.amazonaws.com/1234567890/bounces';
        $count = 5;
        $receipts = [
            base64_encode($faker->uuid),
            base64_encode($faker->uuid),
            base64_encode($faker->uuid),
            base64_encode($faker->uuid),
            base64_encode($faker->uuid),
        ];
        $client = $this->getClientMock(
            array_merge([
            function(CommandInterface $command) use ($queue, $count, $receipts) {
                $this->assertEquals($queue, $command->offsetGet('QueueUrl'));
                $this->assertEquals($count, $command->offsetGet('MaxNumberOfMessages'));
                $dir = __DIR__.'/../DataFixtures/data/';
                return new Result([
                    'Messages' => array_map([$this, 'createMessage'], [
                        $dir.'complaint.json',
                        $dir.'delivery.json',
                        $dir.'soft_bounce.json',
                        $dir.'complaint_feedback_report.json',
                        $dir.'hard_bounce.json',
                    ], $receipts),
                    '@metadata' => $this->createMetadata(),
                ]);
            }],
            array_map(
                function ($receipt) {
                    return function (CommandInterface $command) use ($receipt)
                    {
                        $this->assertSame('DeleteMessage', $command->getName());
                        $this->assertSame($receipt, $command->offsetGet('ReceiptHandle'));

                        return new Result();
                    };
                }, $receipts
            ))
        );
        $container->set('aws.sqs', $client);
        $this->setVerbosityLevel(StreamOutput::VERBOSITY_DEBUG);
        $output = $this->runCommand(
            'civix:sqs:consumer',
            [
                'queue' => $queue,
                'handler' => 'notification_handler',
                '--count' => $count,
            ],
            true
        );

        $this->assertEmpty($output, $output);
        $conn = $container->get('database_connection');
        $cnt = $conn->fetchColumn('SELECT COUNT(*) FROM aws_ses_monitor_messages WHERE sent_from = ?', ['john@example.com']);
        $this->assertEquals(5, $cnt);
        $statuses = $conn->fetchAll('SELECT * FROM aws_ses_monitor_email_statuses WHERE email_address = ?', ['richard@example.com']);
        $this->assertCount(1, $statuses);
        $this->assertEquals(0, $statuses[0]['hard_bounces_count']);
        $this->assertEquals(1, $statuses[0]['soft_bounces_count']);
        $this->assertEquals(2, $statuses[0]['complaints_count']);
        $this->assertEquals(0, $statuses[0]['deliveries_count']);
        $statuses = $conn->fetchAll('SELECT * FROM aws_ses_monitor_email_statuses WHERE email_address = ?', ['jane@example.com']);
        $this->assertCount(1, $statuses);
        $this->assertEquals(1, $statuses[0]['hard_bounces_count']);
        $this->assertEquals(1, $statuses[0]['soft_bounces_count']);
        $this->assertEquals(0, $statuses[0]['complaints_count']);
        $this->assertEquals(1, $statuses[0]['deliveries_count']);
        $cnt = $conn->fetchColumn('SELECT COUNT(*) FROM aws_ses_monitor_deliveries');
        $this->assertEquals(1, $cnt);
        $cnt = $conn->fetchColumn('SELECT COUNT(*) FROM aws_ses_monitor_complaints');
        $this->assertEquals(2, $cnt);
        $cnt = $conn->fetchColumn('SELECT COUNT(*) FROM aws_ses_monitor_bounces');
        $this->assertEquals(3, $cnt);
    }

    private function getClientMock(array $results)
    {
        $mock = new MockHandler();
        foreach ($results as $result) {
            $mock->append($result);
        }
        $client = new SqsClient([
            'region'  => 'us-east-1',
            'version' => 'latest',
            'handler' => $mock,
            'credentials' => new Credentials('key', 'secret')
        ]);

        return $client;
    }

    private function createMessage($fileName, $receipt): array
    {
        $body = $this->createBody($fileName);

        return [
            'MessageId' => '9ef51809-fda4-4a10-b182-902a6a7e1ee5',
            'ReceiptHandle' => $receipt,
            'MD5OfBody' => md5($body),
            'Body' => $body,
            'Attributes' => [
                'SentTimestamp' => '1493868482881',
            ],
        ];
    }

    private function createBody($fileName): string
    {
        return json_encode(
            [
                'Type' => 'Notification',
                'MessageId' => '9ef51809-fda4-4a10-b182-902a6a7e1ee5',
                'TopicArn' => 'arn:aws:sns:us-west-2:123456789012:topic',
                'Subject' => 'Testing publish to subscribed queues',
                'Message' => file_get_contents($fileName),
                'Timestamp' => '2012-03-29T05:12:16.901Z',
                'SignatureVersion' => '1',
                'Signature' => 'EXAMPLEnTrFPa37tnVO0FF9Iau=',
                'SigningCertURL' => 'https://sns.us-east-1.amazonaws.com/cert.pem',
                'UnsubscribeURL' => 'https://sns.us-east-1.amazonaws.com/unsubscribe',
            ]
        );
    }

    private function createMetadata(): array
    {
        return [
            'statusCode' => 200,
            'effectiveUri' => 'https://sqs.us-east-1.amazonaws.com/1234567890/bounces',
            'headers' => [
                'server' => 'Server',
                'date' => 'Thu, 04 May 2017 03:30:38 GMT',
                'content-type' => 'text/xml',
                'content-length' => '5000',
                'connection' => 'keep-alive',
                'x-amzn-requestid' => '2ea1d938-1c01-5443-bc4f-88bcbe9fc0dd',
            ],
            'transferStats' => [
                'http' => [
                    [],
                ],
            ],
        ];
    }
}