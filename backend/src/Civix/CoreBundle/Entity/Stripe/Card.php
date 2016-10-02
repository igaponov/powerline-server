<?php
namespace Civix\CoreBundle\Entity\Stripe;

use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

class Card
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     * @Assert\NotBlank()
     * @Serializer\Expose()
     */
    private $source;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return Card
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param string $source
     * @return Card
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }
}