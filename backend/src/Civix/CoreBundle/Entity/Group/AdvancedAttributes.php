<?php

namespace Civix\CoreBundle\Entity\Group;

use Civix\CoreBundle\Entity\Group;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="group_advanced_attributes", options={"charset"="utf8mb4", "collate"="utf8mb4_unicode_ci", "row_format"="DYNAMIC"})
 * @ORM\Entity()
 * @Serializer\ExclusionPolicy("ALL")
 */
class AdvancedAttributes
{
    /**
     * @var Group
     *
     * @ORM\Id()
     * @ORM\OneToOne(targetEntity="Civix\CoreBundle\Entity\Group", mappedBy="advancedAttributes")
     * @ORM\JoinColumn(name="id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $group;

    /**
     * @var string|null
     *
     * @ORM\Column(name="welcome_message", type="text", nullable=true)
     * @Serializer\Expose()
     */
    private $welcomeMessage;

    /**
     * @var string|null
     *
     * @ORM\Column(name="welcome_video", type="string", nullable=true)
     * @Assert\Url()
     * @Serializer\Expose()
     */
    private $welcomeVideo;

    public function __construct(Group $group)
    {
        $this->group = $group;
    }

    /**
     * @return string
     */
    public function getWelcomeMessage(): ?string
    {
        return $this->welcomeMessage;
    }

    /**
     * @param string $welcomeMessage
     * @return AdvancedAttributes
     */
    public function setWelcomeMessage(?string $welcomeMessage): AdvancedAttributes
    {
        $this->welcomeMessage = $welcomeMessage;

        return $this;
    }

    /**
     * @return string
     */
    public function getWelcomeVideo(): ?string
    {
        return $this->welcomeVideo;
    }

    /**
     * @param string $welcomeVideo
     * @return AdvancedAttributes
     */
    public function setWelcomeVideo(?string $welcomeVideo): AdvancedAttributes
    {
        $this->welcomeVideo = $welcomeVideo;

        return $this;
    }
}