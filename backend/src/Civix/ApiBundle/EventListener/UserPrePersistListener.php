<?php
namespace Civix\ApiBundle\EventListener;

use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Service\PhoneNumberNormalizer;
use Doctrine\ORM\Event\LifecycleEventArgs;
use libphonenumber\NumberParseException;
use Psr\Log\LoggerInterface;

class UserPrePersistListener
{
    /**
     * @var PhoneNumberNormalizer
     */
    private $phoneNumberNormalizer;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        PhoneNumberNormalizer $phoneNumberNormalizer,
        LoggerInterface $logger
    )
    {
        $this->phoneNumberNormalizer = $phoneNumberNormalizer;
        $this->logger = $logger;
    }

    public function prePersist(LifecycleEventArgs $event)
    {
        $user = $event->getEntity();

        if (!$user instanceof User) {
            return;
        }

        try {
            $phone = $this->phoneNumberNormalizer->normalize($user->getPhone(), $user->getCountry());
            $user->setPhone($phone);
        } catch (NumberParseException $e) {
            $this->logger->warning($e->getMessage(), ['phone' => $user->getPhone()]);
        }
    }
}