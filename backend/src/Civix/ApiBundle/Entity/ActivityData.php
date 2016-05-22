<?php
namespace Civix\ApiBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;

class ActivityData
{
    /**
     * @var integer
     * 
     * @Assert\NotBlank()
     */
    private $id;

    /**
     * @var boolean
     * 
     * @Assert\NotBlank()
     */
    private $read;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return ActivityData
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRead()
    {
        return $this->read;
    }

    /**
     * @param mixed $read
     * @return ActivityData
     */
    public function setRead($read)
    {
        $this->read = $read;

        return $this;
    }
}