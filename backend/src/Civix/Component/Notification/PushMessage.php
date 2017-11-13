<?php

namespace Civix\Component\Notification;

use Civix\Component\Notification\Model\RecipientInterface;

class PushMessage
{
    /**
     * @var string
     */
    private $title;
    /**
     * @var string
     */
    private $message;
    /**
     * @var string
     */
    private $type;
    /**
     * @var array
     */
    private $data;
    /**
     * @var string
     */
    private $image;
    /**
     * @var RecipientInterface
     */
    private $recipient;

    /**
     * @var integer|null
     */
    private $badge;

    public function __construct(
        RecipientInterface $recipient,
        string $title,
        string $message,
        string $type,
        array $data = [],
        string $image = ''
    ) {
        $this->title = $title;
        $this->message = $message;
        $this->type = $type;
        $this->data = $data;
        $this->image = $image;
        $this->recipient = $recipient;
    }

    /**
     * @return RecipientInterface
     */
    public function getRecipient(): RecipientInterface
    {
        return $this->recipient;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return string
     */
    public function getImage(): string
    {
        return $this->image;
    }

    /**
     * @param string $image
     * @return PushMessage
     */
    public function setImage(string $image): PushMessage
    {
        $this->image = $image;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getBadge(): ?int
    {
        return $this->badge;
    }

    /**
     * @param int|null $badge
     * @return PushMessage
     */
    public function setBadge(int $badge): PushMessage
    {
        $this->badge = $badge;

        return $this;
    }
}