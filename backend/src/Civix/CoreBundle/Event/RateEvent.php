<?php
namespace Civix\CoreBundle\Event;

use Civix\CoreBundle\Entity\BaseCommentRate;
use Symfony\Component\EventDispatcher\Event;

class RateEvent extends Event
{
    /**
     * @var BaseCommentRate
     */
    private $rate;

    public function __construct(BaseCommentRate $rate)
    {
        $this->rate = $rate;
    }

    /**
     * @return BaseCommentRate
     */
    public function getRate()
    {
        return $this->rate;
    }
}