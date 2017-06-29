<?php

namespace Civix\CoreBundle\Entity\Group;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(
 *     name="group_tags",
 *     options={"charset"="utf8mb4", "collate"="utf8mb4_unicode_ci", "row_format"="DYNAMIC"},
 *     uniqueConstraints={@ORM\UniqueConstraint(columns={"name"})}
 * )
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\Group\TagRepository")
 * @UniqueEntity(fields={"name"})
 * @Serializer\ExclusionPolicy("ALL")
 */
class Tag
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose()
     * @Serializer\Groups({"group-tag"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     * @Serializer\Expose()
     * @Serializer\Groups({"group-tag"})
     */
    private $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}