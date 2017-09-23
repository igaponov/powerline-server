<?php

namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Event\CiceroRepresentativeEvent;
use Civix\CoreBundle\Event\CiceroRepresentativeEvents;
use Civix\CoreBundle\Service\ProPublicaRepresentativePopulator;
use GuzzleHttp\Command\ServiceClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProPublicaSubscriber implements EventSubscriberInterface
{
    /**
     * @var ServiceClientInterface
     */
    private $client;
    /**
     * @var ProPublicaRepresentativePopulator
     */
    private $populator;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public static function getSubscribedEvents(): array
    {
        return [
            CiceroRepresentativeEvents::UPDATE => 'getInfo',
        ];
    }

    public function __construct(
        ServiceClientInterface $client,
        ProPublicaRepresentativePopulator $populator,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->populator = $populator;
        $this->logger = $logger;
    }

    public function getInfo(CiceroRepresentativeEvent $event)
    {
        $representative = $event->getRepresentative();

        if (!$representative->getBioguide()) {
            return;
        }

        try {
            /** @noinspection PhpUndefinedMethodInspection */
            $data = $this->client->getMember(['id' => $representative->getBioguide()]);
        } catch (\Exception $e) {
            $this->logger->critical('ProPublica error: '.$e->getMessage(), ['e' => $e]);
            return;
        }

        $this->populator->populate($representative, $data['results'][0]);
    }
}