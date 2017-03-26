<?php

namespace Civix\CoreBundle\Entity\Report;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="petition_response_report", options={"charset"="utf8mb4", "collate"="utf8mb4_unicode_ci", "row_format"="DYNAMIC"})
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\Report\PetitionResponseRepository")
 */
class PetitionResponseReport
{
    /**
     * @var int
     *
     * @ORM\Id()
     * @ORM\Column(name="user_id", type="integer", length=11)
     */
    private $user;

    /**
     * @var int
     *
     * @ORM\Id()
     * @ORM\Column(name="petition_id", type="integer", length=11)
     */
    private $petition;

    public function __construct(int $user, int $post)
    {
        $this->user = $user;
        $this->petition = $post;
    }
}