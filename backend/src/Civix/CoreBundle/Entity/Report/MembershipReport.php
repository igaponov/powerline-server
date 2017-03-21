<?php

namespace Civix\CoreBundle\Entity\Report;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="membership_report", options={"charset"="utf8mb4", "collate"="utf8mb4_unicode_ci", "row_format"="DYNAMIC"})
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\Report\MembershipReportRepository")
 */
class MembershipReport
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
     * @ORM\Column(name="group_id", type="integer", length=11)
     */
    private $group;

    /**
     * @var array
     *
     * @ORM\Column(name="group_fields", type="json_array")
     */
    private $fields = [];

    public function __construct(int $user, int $group, array $fields = [])
    {
        $this->user = $user;
        $this->group = $group;
        $this->fields = $fields;
    }
}