<?php

namespace Civix\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\StateRepository")
 * @ORM\Table(name="states")
 */
class State
{
    /**
     * @ORM\Id
     * @ORM\Column(name="code", type="string", length=2, unique=true)
     */
    protected $code;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=250)
     */
    protected $name;

    /**
     * @ORM\OneToMany(targetEntity="Group", mappedBy="localState")
     */
    protected $localGroups;

    public function __construct()
    {
        $this->localGroups = new ArrayCollection();
    }

    /**
     * Get id.
     *
     * @return string
     */
    public function getId()
    {
        return $this->code;
    }

    /**
     * Set code.
     *
     * @param string $code
     *
     * @return State
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return State
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

    public function __toString()
    {
        return $this->getCode();
    }

    /**
     * Add localGroups.
     *
     * @param Group $localGroups
     *
     * @return State
     */
    public function addLocalGroup(Group $localGroups)
    {
        $this->localGroups[] = $localGroups;

        return $this;
    }

    /**
     * Remove localGroups.
     *
     * @param Group $localGroups
     */
    public function removeLocalGroup(Group $localGroups)
    {
        $this->localGroups->removeElement($localGroups);
    }

    /**
     * Get localGroups.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLocalGroups()
    {
        return $this->localGroups;
    }
}
