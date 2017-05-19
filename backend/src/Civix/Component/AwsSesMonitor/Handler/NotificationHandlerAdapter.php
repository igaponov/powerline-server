<?php

namespace Civix\Component\AwsSesMonitor\Handler;

use Aws\Credentials\Credentials;
use SerendipityHQ\Bundle\AwsSesMonitorBundle\Service\NotificationHandler;
use Symfony\Component\HttpFoundation\Request;

class NotificationHandlerAdapter implements NotificationHandlerInterface
{
    /**
     * @var NotificationHandler
     */
    private $notificationHandler;

    /**
     * @param NotificationHandler $notificationHandler
     */
    public function __construct(NotificationHandler $notificationHandler)
    {
        $this->notificationHandler = $notificationHandler;
    }

    public function handle(string $message)
    {
        $server = ['REQUEST_METHOD' => 'POST'];
        $request = new Request([], [], [], [], [], $server, $message);
        $result = $this->notificationHandler->handleRequest($request, new Credentials('', ''));
        if ($result['code'] !== 200) {
            throw new \RuntimeException($result['content'], $result['code']);
        }
    }
}