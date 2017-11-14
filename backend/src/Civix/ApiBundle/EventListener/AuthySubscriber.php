<?php

namespace Civix\ApiBundle\EventListener;

use Civix\CoreBundle\Event\UserEvent;
use Civix\CoreBundle\Event\UserEvents;
use GuzzleHttp\Command\Exception\CommandException;
use GuzzleHttp\Command\ServiceClientInterface;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AuthySubscriber implements EventSubscriberInterface
{
    /**
     * @var ServiceClientInterface
     */
    private $client;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public static function getSubscribedEvents(): array
    {
        return [
            UserEvents::REGISTRATION => [['startVerification', -1000]],
        ];
    }

    public function __construct(ServiceClientInterface $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    public function startVerification(UserEvent $event)
    {
        $user = $event->getUser();
        $phoneNumber = $user->getPhone();

        if (!$phoneNumber) {
            return;
        }

        $phoneUtil = PhoneNumberUtil::getInstance();
        $type = $phoneUtil->getNumberType($phoneNumber);
        try {
            call_user_func([$this->client, 'startVerification'], [
                'country_code' => $phoneNumber->getCountryCode(),
                'phone_number' => $phoneNumber->getNationalNumber(),
                'via' => $type === PhoneNumberType::MOBILE ? 'sms' : 'call',
            ]);
        } catch (CommandException $e) {
            $this->logger->critical('Phone verification error: '.$e->getMessage(), [
                'user' => $user->getId(),
                'e' => $e,
            ]);
        }
    }
}