<?php

namespace Civix\Component\Notification\Retriever;

use Civix\Component\Notification\Model\AbstractEndpoint;
use Civix\Component\Notification\Model\RecipientInterface;
use Doctrine\Common\Persistence\ObjectManager;

class ObjectEndpointRetriever implements EndpointRetrieverInterface
{
    /**
     * @var ObjectManager
     */
    private $om;

    public function __construct(ObjectManager $om)
    {
        $this->om = $om;
    }

    public function retrieve(RecipientInterface $recipient): array
    {
        $repository = $this->om->getRepository(AbstractEndpoint::class);

        return $repository->findBy(['user' => $recipient]);
    }
}