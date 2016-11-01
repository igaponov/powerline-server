<?php
namespace Civix\CoreBundle\Model\Group;

use Symfony\Component\Validator\Constraints as Assert;

class WorksheetField
{
    /**
     * @var integer
     * @Assert\NotBlank()
     */
    private $id;

    /**
     * @var string
     * @Assert\NotBlank()
     */
    private $value;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return WorksheetField
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     * @return WorksheetField
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }
}