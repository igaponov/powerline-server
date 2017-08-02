<?php

namespace Civix\Component\Notification\Retriever;

use Civix\Component\Notification\Model\RecipientInterface;

interface RetrieverInterface
{
    /**
     * @param RecipientInterface $recipient
     * @return array
     */
    public function retrieve(RecipientInterface $recipient): array;
}