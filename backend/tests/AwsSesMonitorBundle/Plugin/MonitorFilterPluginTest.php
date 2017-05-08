<?php

namespace Tests\AwsSesMonitorBundle\Plugin;

use Tests\AwsSesMonitorBundle\DataFixtures\ORM\LoadEmailMonitorData;
use Liip\FunctionalTestBundle\Test\WebTestCase;

class MonitorFilterPluginTest extends WebTestCase
{
    public function testFiltration()
    {
        $this->loadFixtures([LoadEmailMonitorData::class]);
        $container = $this->getContainer();
        $mailer = $container->get('mailer');
        $message = \Swift_Message::newInstance('Test subj', 'Test body', 'text/plain');
        $message->setFrom('support@powerli.ne')
            ->setTo('jane@example.com')
            ->setCc(['richard@example.com', 'john@example.com'])
            ->setBcc('john@example.com');
        $count = $mailer->send($message);
        $this->assertEquals(1, $count);
        $headers = $message->getHeaders();
        /** @var \Swift_Mime_Headers_MailboxHeader $to */
        $to = $headers->get('to');
        /** @var \Swift_Mime_Headers_MailboxHeader $cc */
        $cc = $headers->get('cc');
        /** @var \Swift_Mime_Headers_MailboxHeader $bcc */
        $bcc = $headers->get('bcc');
        $this->assertEmpty($to->getAddresses());
        $this->assertEmpty($bcc->getAddresses());
        $this->assertEquals(['richard@example.com'], $cc->getAddresses());
    }
}