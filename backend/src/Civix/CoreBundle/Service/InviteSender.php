<?php

namespace Civix\CoreBundle\Service;

use Civix\CoreBundle\Entity\Invites\UserToGroup;

class InviteSender
{
    /**
     * @var QueueTaskInterface
     */
    private $pushTask;

    public function __construct(QueueTaskInterface $pushTask)
    {
        $this->pushTask = $pushTask;
    }

    /**
     * @param array $invites
     * @deprecated Use {@link \Civix\CoreBundle\EventListener\PushSenderSubscriber::sendUserToGroupInvites listener} instead
     */
    public function sendUserToGroupInvites(array $invites): void
    {
        /* @var $invite UserToGroup */
        foreach ($invites as $invite) {
            $this->pushTask->addToQueue(
                'sendGroupInvitePush',
                [$invite->getUser()->getId(), $invite->getGroup()->getId()],
                []
            );
        }
    }
}
