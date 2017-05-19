<?php

namespace Civix\CoreBundle\Service;

use Doctrine\ORM\EntityManager;
use Aws\Sns\SnsClient;
use Aws\Sns\Exception;
use Civix\CoreBundle\Entity\Notification as Model;
use Psr\Log\LoggerInterface;

class Notification
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var SnsClient
     */
    private $sns;

    /**
     * @var string
     */
    private $androidArn;

    /**
     * @var string
     */
    private $iosArn;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(EntityManager $em, SnsClient $sns, $androidArn, $iosArn, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->sns = $sns;
        $this->androidArn = $androidArn;
        $this->iosArn = $iosArn;
        $this->logger = $logger;
    }

    public function handleEndpoint(Model\AbstractEndpoint $newEndpoint)
    {
        $endpoints = $this->em->getRepository(get_class($newEndpoint))->createQueryBuilder('e')
            ->where('e.token = :token OR e.user = :user')
            ->setParameter('token', $newEndpoint->getToken())
            ->setParameter('user', $newEndpoint->getUser())
            ->getQuery()
            ->getResult()
        ;

        $this->removeEndpoints($endpoints);

        $this->addEndpoint($newEndpoint);

        return $newEndpoint;
    }

    public function send($title, $message, $type, $entityData, $image, Model\AbstractEndpoint $endpoint, $badge = null)
    {
        try {
            $platformMessage = $endpoint->getPlatformMessage($title, $message, $type, $entityData, $image, $badge);
            $this->sns->publish(array(
                'TargetArn' => $endpoint->getArn(),
                'MessageStructure' => 'json',
                'Message' => $platformMessage
            ));
            $this->logger->debug(
                'Message is pushed '.str_replace('\\', '', $platformMessage),
                $endpoint->getContext()
            );
        } catch (Exception\SnsException $e) {
            if ($e->getAwsErrorCode() === 'EndpointDisabled') {
                $this->logger->debug($e->getAwsErrorMessage(), $endpoint->getContext());
                $this->removeEndpoint($endpoint);
            } else {
                $this->logger->critical($e->getAwsErrorMessage(), $endpoint->getContext());
            }
        }
    }

    private function removeEndpoints($endpoints)
    {
        /* @var $endpoint Model\AbstractEndpoint */
        foreach ($endpoints as $endpoint) {
            $this->removeEndpoint($endpoint);
        }
    }

    private function removeEndpoint(Model\AbstractEndpoint $endpoint)
    {
        $this->sns->deleteEndpoint([
            'EndpointArn' => $endpoint->getArn(),
        ]);
        $this->em->remove($endpoint);
        $this->em->flush($endpoint);
    }

    private function addEndpoint(Model\AbstractEndpoint $endpoint)
    {
        try {
            $result = $this->sns->createPlatformEndpoint([
                'PlatformApplicationArn' => $this->getPlatformArn($endpoint),
                'Token' => $endpoint->getToken(),
                'CustomUserData' => $endpoint->getUser()->getId(),
            ]);
        } catch (Exception\SnsException $e) {
            if (preg_match(
                '/Endpoint (.*) already exists/',
                $e->getAwsErrorMessage(),
                $matches
            )) {
                $this->sns->deleteEndpoint(array(
                    'EndpointArn' => $matches[1],
                ));
                $result = $this->sns->createPlatformEndpoint([
                    'PlatformApplicationArn' => $this->getPlatformArn($endpoint),
                    'Token' => $endpoint->getToken(),
                    'CustomUserData' => $endpoint->getUser()->getId(),
                ]);
            } else {
                throw $e;
            }
        }

        $endpoint->setArn($result['EndpointArn']);

        $this->em->persist($endpoint);
        $this->em->flush($endpoint);
    }

    private function getPlatformArn(Model\AbstractEndpoint $endpoint)
    {
        if ($endpoint instanceof Model\AndroidEndpoint) {
            return $this->androidArn;
        }
        if ($endpoint instanceof Model\IOSEndpoint) {
            return $this->iosArn;
        }
        throw new \InvalidArgumentException(
            sprintf('Endpoint with class %s is not supported', get_class($endpoint))
        );
    }
}
