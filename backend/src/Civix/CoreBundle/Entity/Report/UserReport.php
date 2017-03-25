<?php

namespace Civix\CoreBundle\Entity\Report;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="user_report", options={"charset"="utf8mb4", "collate"="utf8mb4_unicode_ci", "row_format"="DYNAMIC"})
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\Report\UserReportRepository")
 */
class UserReport
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
     * @ORM\Column(type="integer", options={"default" = 0})
     */
    private $followers = 0;

    /**
     * @var array
     *
     * @ORM\Column(type="json_array")
     */
    private $representatives = [];

    /**
     * @var string
     *
     * @ORM\Column(name="country", options={"default" = ""})
     */
    private $country;

    /**
     * @var string
     *
     * @ORM\Column(name="state", options={"default" = ""})
     */
    private $state;

    /**
     * @var string
     *
     * @ORM\Column(name="locality", options={"default" = ""})
     */
    private $locality;

    /**
     * @var array
     *
     * @ORM\Column(type="json_array")
     */
    private $districts = [];

    public function __construct(int $user, int $followers = 0, array $representatives = [], $country = '', $state = '', $locality = '', $districts = [])
    {
        $this->user = $user;
        $this->followers = $followers;
        $this->representatives = $representatives;
        $this->country = $country;
        $this->state = $state;
        $this->locality = $locality;
        $this->districts = $districts;
    }
}