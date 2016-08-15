<?php

namespace Civix\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * HashTag.
 *
 * @ORM\Table(name="hash_tags", indexes={
 *      @ORM\Index(name="hash_tag_name_ind", columns={"name"})
 * })
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\HashTagRepository")
 */
class HashTag
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return HashTag
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Constructor.
     * @param null|string $name
     */
    public function __construct($name = null)
    {
        $this->name = $name;
    }
}
