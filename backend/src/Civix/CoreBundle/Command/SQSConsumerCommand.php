<?php

namespace Civix\CoreBundle\Command;

use Aws\Exception\AwsException;
use Civix\Component\AwsSesMonitor\Handler\NotificationHandlerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SQSConsumerCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('civix:sqs:consumer')
            ->addArgument('queue', InputArgument::REQUIRED, 'Queue arn')
            ->addArgument('handler', InputArgument::REQUIRED, 'Queue handler')
            ->addOption('count', 'c', InputOption::VALUE_OPTIONAL, 'Message count', 1);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queueUrl = $input->getArgument('queue');
        $count = min(10, max(1, (int)$input->getOption('count')));
        $container = $this->getContainer();
        $handler = $container->get($input->getArgument('handler'));
        if (!$handler instanceof NotificationHandlerInterface) {
            throw new \RuntimeException('Command supports only handlers with \Civix\Component\AwsSesMonitor\Handler\NotificationHandlerInterface interface.');
        }
        $client = $container->get('aws.sqs');
        $logger = $container->get('logger');
        try {
            $result = $client->receiveMessage([
                'AttributeNames' => ['SentTimestamp'],
                'MaxNumberOfMessages' => $count,
                'MessageAttributeNames' => ['All'],
                'QueueUrl' => $queueUrl,
                'WaitTimeSeconds' => 0,
            ]);
            if (count($result->get('Messages')) > 0) {
                foreach ($result->get('Messages') as $message) {
                    $handler->handle($message['Body']);
                    $client->deleteMessage([
                        'QueueUrl' => $queueUrl,
                        'ReceiptHandle' => $message['ReceiptHandle'],
                    ]);
                }
            } else {
                $output->writeln("No messages in queue");
            }
        } catch (AwsException $e) {
            $logger->critical($e->getMessage(), ['exception' => $e]);
        }
    }
}