<?php

namespace Civix\CoreBundle\Service\RabbitMQCallback;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;

class PushQueue implements ConsumerInterface
{
    /**
     * @var array
     */
    private $executorsArray;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        array $executorsArray,
        LoggerInterface $logger
    ) {
        $this->executorsArray = $executorsArray;
        $this->logger = $logger;
    }

    public function execute(AMQPMessage $msg)
    {
        $executeParams = unserialize($msg->body);

        $handled = false;
        try {
            foreach ($this->executorsArray as $executorObj) {
                if (method_exists($executorObj, $executeParams['method'])) {
                    call_user_func_array(
                        [$executorObj, $executeParams['method']],
                        $executeParams['params']
                    );
                    $handled = true;
                }
            }
            if (!$handled) {
                $this->logger->critical('No handler found for message.', ['message' => $msg]);
            }
        } catch (\Throwable $e) {
            $this->logger->critical('Message handling failed.', ['message' => $msg, 'e' => $e]);
        }

        return true;
    }
}
