<?php

namespace Civix\Component\Notification\Adapter;

use Aws\Sns\Exception;
use Aws\Sns\SnsClient;
use Civix\Component\Notification\DataFactory\DataFactoryInterface;
use Civix\Component\Notification\Exception\AWSNotificationException;
use Civix\Component\Notification\Exception\NotificationException;
use Civix\Component\Notification\Handler\HandlerInterface;
use Civix\Component\Notification\Model\AbstractEndpoint;
use Civix\Component\Notification\PushMessage;
use Civix\Component\Notification\Retriever\EndpointRetrieverInterface;

class AWSAdapter implements AdapterInterface
{
    /**
     * @var EndpointRetrieverInterface
     */
    private $retriever;
    /**
     * @var SnsClient
     */
    private $sns;
    /**
     * @var DataFactoryInterface
     */
    private $dataFactory;

    public function __construct(
        EndpointRetrieverInterface $retriever,
        SnsClient $sns,
        DataFactoryInterface $dataFactory
    ) {
        $this->retriever = $retriever;
        $this->sns = $sns;
        $this->dataFactory = $dataFactory;
    }

    public function send(PushMessage $message, HandlerInterface $callback): void
    {
        $endpoints = $this->retriever->retrieve($message->getRecipient());
        foreach ($endpoints as $endpoint) {
            try {
                $callback(null, $this->sendToEndpoint($message, $endpoint));
            } catch (NotificationException $e) {
                $callback($e);
            }
        }
    }

    private function sendToEndpoint(PushMessage $message, AbstractEndpoint $endpoint): array
    {
        try {
            $platformMessage = $this->dataFactory->createData($message, $endpoint);
            $this->sns->publish([
                'TargetArn' => $endpoint->getArn(),
                'MessageStructure' => 'json',
                'Message' => json_encode($platformMessage)
            ]);

            return $platformMessage;
        } catch (Exception\SnsException $e) {
            $text = $e->getAwsErrorMessage();
            if ($e->getAwsErrorCode() === 'EndpointDisabled') {
                $level = NotificationException::CODE_DEBUG;
            } else {
                $level = NotificationException::CODE_CRITICAL;
            }
            throw new AWSNotificationException($text, $e, $endpoint, $level);
        }
    }
}
