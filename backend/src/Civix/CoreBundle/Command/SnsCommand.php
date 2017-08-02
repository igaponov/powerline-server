<?php

namespace Civix\CoreBundle\Command;

use Aws\Sns\Exception\SnsException;
use Civix\Component\Notification\Model\AbstractEndpoint;
use Civix\Component\Notification\PushMessage;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SnsCommand extends ContainerAwareCommand
{
    /**
     * @var \Aws\Sns\SnsClient
     */
    private $client;
    /**
     * @var OutputInterface
     */
    private $output;

    protected function configure(): void
    {
        $this
            ->setName('sns')
            ->setDescription('Debug sending of push notification with amazon SNS')
            ->addOption(
                'list-android',
                null,
                InputOption::VALUE_NONE,
                'List of endpoints for android'
            )->addOption(
                'list-ios',
                null,
                InputOption::VALUE_NONE,
                'List of endpoints for ios'
            )->addOption(
                'delete',
                null,
                InputOption::VALUE_NONE,
                'Delete endpoint'
            )->addArgument(
                'endpoint',
                InputArgument::OPTIONAL, 'Send notification to specified endpoint'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->client = $client = $this->getContainer()->get('aws.sns');

        if ($input->getOption('list-android')) {
            $this->showEndpoints(
                $this->getContainer()->getParameter('amazon_sns.android_arn'),
                'List of GCM endpoints:'
            );
        }

        if ($input->getOption('list-ios')) {
            $this->showEndpoints($this->getContainer()->getParameter('amazon_sns.ios_arn'), 'List of APNS endpoints:');
        }

        $endpoint = $input->getArgument('endpoint');

        if ($endpoint) {
            if ($input->getOption('delete')) {
                $output->writeln('<comment>Delete endpoint</comment>');
                $client->deleteEndpoint(array(
                    'EndpointArn' => $endpoint,
                ));
            } else {
                /** @var EntityManager $em */
                $em = $this->getContainer()->get('doctrine.orm.entity_manager');
                $endpoint = $em->getRepository(AbstractEndpoint::class)->findOneBy(['arn' => $endpoint]);
                if (!$endpoint) {
                    return;
                }
                $testMessage = 'Test notification';
                $service = $this->getContainer()->get('civix.notification.sender');
                try {
                    $message = new PushMessage(
                        $endpoint->getUser(),
                        'Test title',
                        $testMessage,
                        'test-message',
                        ['property' => 'value'],
                        null
                    );
                    $message->setBadge(5);
                    $service->send($message);
                } catch (SnsException $e) {
                    $output->writeln("<error>{$e->getMessage()}</error>");
                }
            }
        }
    }

    private function showEndpoints($platformArn, $title = 'List of endpoints:')
    {
        $result = $this->client->listEndpointsByPlatformApplication(array(
            'PlatformApplicationArn' => $platformArn,
        ));
        $output = $this->output;
        $output->writeln("<comment>{$title}</comment>");
        foreach ($result['Endpoints'] as $endpoint) {
            $output->writeln($endpoint['EndpointArn']);
            $output->writeln("<info>CustomUserData:</info> {$endpoint['Attributes']['CustomUserData']}");
            $output->writeln("<info>Enabled:</info> {$endpoint['Attributes']['Enabled']}");
        }
    }
}
