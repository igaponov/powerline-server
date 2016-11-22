<?php

namespace Civix\CoreBundle\Entity\Group;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\GroupContentInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;
use Civix\CoreBundle\Entity\User;

/**
 * Field entity.
 *
 * @ORM\Table(name="groups_fields")
 * @ORM\Entity()
 * @Serializer\ExclusionPolicy("all")
 */
class GroupField implements GroupContentInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-groups-fields", "api-group-field"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="field_name", type="string", length=150)
     * @Assert\NotBlank()
     * @Serializer\Expose()
     * @Serializer\Groups({"api-groups-fields", "api-group-field"})
     */
    private $fieldName;

    /**
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\Group", inversedBy="fields")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    private $group;

    /**
     * @ORM\OneToMany(
     *      targetEntity="Civix\CoreBundle\Entity\Group\FieldValue",
     *      mappedBy="field", cascade={"persist"}, orphanRemoval=true
     * )
     */
    private $values;

    public function __construct()
    {
        $this->values = new ArrayCollection();
    }

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
     * Set fieldName.
     *
     * @param string $fieldName
     *
     * @return GroupField
     */
    public function setFieldName($fieldName)
    {
        $this->fieldName = $fieldName;

        return $this;
    }

    /**
     * Get fieldName.
     *
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * Set group.
     *
     * @param Group $group
     *
     * @return GroupField
     */
    public function setGroup(Group $group = null)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Get group.
     *
     * @return Group
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * Add values.
     *
     * @param FieldValue $values
     *
     * @return GroupField
     */
    public function addValue(FieldValue $values)
    {
        $this->values[] = $values;

        return $this;
    }

    /**
     * Remove values.
     *
     * @param FieldValue $values
     */
    public function removeValue(FieldValue $values)
    {
        $this->values->removeElement($values);
    }

    /**
     * Get values.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getValues()
    {
        return $this->values;
    }

    public function getUserValue(User $user)
    {
        /* @var FieldValue $value */
        foreach ($this->values as $value) {
            if ($value->getUser() === $user) {
                return $value->getFieldValue();
            }
        }

        return null;
    }
}
