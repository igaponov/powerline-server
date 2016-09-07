<?php

namespace Civix\CoreBundle\Entity\Group;

use Civix\CoreBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use JMS\Serializer\Annotation as Serializer;

/**
 * Field's values entity.
 *
 * @ORM\Table(name="groups_fields_values")
 * @ORM\Entity(repositoryClass="Civix\CoreBundle\Repository\Group\FieldValueRepository")
 * @UniqueEntity(
 *     fields={"field", "fieldValue", "user"}
 * )
 * @Serializer\ExclusionPolicy("all")
 */
class FieldValue
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
     * @Serializer\Expose()
     * @Serializer\Groups({"api-group-field"})
     * @ORM\Column(name="field_value", type="string", length=255)
     */
    private $fieldValue;

    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"api-group-field"})
     * @Serializer\Type("Civix\CoreBundle\Entity\Group\GroupField")
     * @ORM\ManyToOne(
     *      targetEntity="Civix\CoreBundle\Entity\Group\GroupField",
     *      inversedBy="values",
     *      cascade={"persist","remove"}
     * )
     * @ORM\JoinColumn(name="field_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    private $field;

    /**
     * @ORM\ManyToOne(targetEntity="\Civix\CoreBundle\Entity\User", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    private $user;

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
     * Set fieldValue.
     *
     * @param string $fieldValue
     *
     * @return FieldValue
     */
    public function setFieldValue($fieldValue)
    {
        $this->fieldValue = $fieldValue;

        return $this;
    }

    /**
     * Get fieldValue.
     *
     * @return string
     */
    public function getFieldValue()
    {
        return $this->fieldValue;
    }

    /**
     * Set field.
     *
     * @param GroupField $field
     *
     * @return FieldValue
     */
    public function setField(GroupField $field = null)
    {
        $this->field = $field;

        return $this;
    }

    /**
     * Get field.
     *
     * @return GroupField
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Set user.
     *
     * @param User $user
     *
     * @return FieldValue
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }
}
