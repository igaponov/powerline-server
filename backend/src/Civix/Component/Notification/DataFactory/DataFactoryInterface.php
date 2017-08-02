<?php

namespace Civix\Component\Notification\DataFactory;

use Civix\Component\Notification\Model\ModelInterface;
use Civix\Component\Notification\PushMessage;

interface DataFactoryInterface
{
    public function createData(PushMessage $message, ModelInterface $model): array;
}