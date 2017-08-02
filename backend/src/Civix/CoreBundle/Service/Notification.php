<?php

namespace Civix\CoreBundle\Service;

use Aws\Sns\Exception;
use Aws\Sns\SnsClient;
use Civix\Component\Notification\Model\AbstractEndpoint;
use Civix\Component\Notification\Model\AndroidEndpoint;
use Civix\Component\Notification\Model\IOSEndpoint;
use Doctrine\ORM\EntityManager;

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

    public function __construct(EntityManager $em, SnsClient $sns, $androidArn, $iosArn)
    {
        $this->em = $em;
        $this->sns = $sns;
        $this->androidArn = $androidArn;
        $this->iosArn = $iosArn;
    }

    public function handleEndpoint(AbstractEndpoint $newEndpoint): AbstractEndpoint
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

    /**
     * @param AbstractEndpoint[] $endpoints
     */
    private function removeEndpoints(array $endpoints): void
    {
        foreach ($endpoints as $endpoint) {
            $this->removeEndpoint($endpoint);
        }
    }

    private function removeEndpoint(AbstractEndpoint $endpoint): void
    {
        $this->sns->deleteEndpoint([
            'EndpointArn' => $endpoint->getArn(),
        ]);
        $this->em->remove($endpoint);
        $this->em->flush($endpoint);
    }

    private function addEndpoint(AbstractEndpoint $endpoint): void
    {
        try {
            $result = $this->sns->createPlatformEndpoint([
                'PlatformApplicationArn' => $this->getPlatformArn($endpoint),
                'Token' => $endpoint->getToken(),
                'CustomUserData' => $endpoint->getUser() ? $endpoint->getUser()->getId() : null,
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

    private function getPlatformArn(AbstractEndpoint $endpoint): string
    {
        if ($endpoint instanceof AndroidEndpoint) {
            return $this->androidArn;
        }
        if ($endpoint instanceof IOSEndpoint) {
            return $this->iosArn;
        }
        throw new \InvalidArgumentException(
            sprintf('Endpoint with class %s is not supported', get_class($endpoint))
        );
    }
}
