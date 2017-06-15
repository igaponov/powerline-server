<?php

namespace Civix\CoreBundle\Entity\Group;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\GroupContentInterface;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Link entity.
 *
 * @ORM\Table(name="links", options={"charset"="utf8mb4", "collate"="utf8mb4_unicode_ci", "row_format"="DYNAMIC"})
 * @ORM\Entity()
 * @Serializer\ExclusionPolicy("ALL")
 */
class Link implements GroupContentInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose()
     * @Serializer\Groups({"group-link"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     * @Assert\Url()
     * @Serializer\Expose()
     * @Serializer\Groups({"group-link"})
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     * @Serializer\Expose()
     * @Serializer\Groups({"group-link"})
     */
    private $label;

    /**
     * @var Group
     *
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\Group")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @Assert\Valid()
     */
    private $group;

    public function __construct(string $url, string $label)
    {
        $this->url = $url;
        $this->label = $label;
    }

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     * @return Link
     */
    public function setUrl(string $url): Link
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param string $label
     * @return Link
     */
    public function setLabel(string $label): Link
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return Group
     */
    public function getGroup(): Group
    {
        return $this->group;
    }

    /**
     * @param Group $group
     * @return Link
     */
    public function setGroup(Group $group): Link
    {
        $this->group = $group;

        return $this;
    }
}