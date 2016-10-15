<?php
namespace Civix\CoreBundle\Event;

use Civix\CoreBundle\Model\Group\Worksheet;
use Symfony\Component\EventDispatcher\Event;

class InquiryEvent extends Event
{
    /**
     * @var Worksheet
     */
    private $worksheet;

    public function __construct(Worksheet $worksheet)
    {
        $this->worksheet = $worksheet;
    }

    /**
     * @return Worksheet
     */
    public function getWorksheet()
    {
        return $this->worksheet;
    }
}