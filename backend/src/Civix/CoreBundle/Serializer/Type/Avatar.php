<?php

namespace Civix\CoreBundle\Serializer\Type;

use Civix\CoreBundle\Entity\HasAvatarInterface;

class Avatar extends Image
{
    /**
     * @var bool
     */
    protected $privacy;

    public function __construct(HasAvatarInterface $entity, $privacy = false)
    {
        parent::__construct($entity, 'avatar');
        $this->privacy = $privacy;
    }

    public function isPrivacy()
    {
        return $this->privacy;
    }
}
