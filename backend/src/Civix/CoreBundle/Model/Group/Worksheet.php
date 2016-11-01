<?php

namespace Civix\CoreBundle\Model\Group;

use Civix\CoreBundle\Validator\Constraints\PackageState;
use Civix\CoreBundle\Validator\Constraints\Passcode;
use JMS\Serializer\Annotation as Serializer;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ExecutionContextInterface;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\Group;

/**
 * @Serializer\ExclusionPolicy("all")
 * @Assert\Callback(methods={"isCorrectRequiredFields"}, groups={"api-group-field"})
 * @Assert\Callback(methods={"isCorrectRequiredAnsweredFields"}, groups={"group-join"})
 * @Passcode(groups={"group-join"})
 */
class Worksheet
{
    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"api-group-field"})
     * @Serializer\Type("ArrayCollection<Civix\CoreBundle\Entity\Group\FieldValue>")
     * @Assert\NotBlank(groups={"api-group-field"})
     * @Assert\Collection(
     *      fields={
     *          "field" =  @Assert\Required({@Assert\NotBlank(groups={"api-group-field"})}),
     *          "field_value" = @Assert\Required({@Assert\NotBlank(groups={"api-group-field"})})
     *      }
     * )
     */
    private $fields;

    /**
     * @var WorksheetField[]
     */
    private $answeredFields;

    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"api-group-passcode"})
     * @Serializer\Type("string")
     * @Assert\NotBlank(groups={"api-group-passcode"})
     */
    private $passcode;

    /**
     * @var Group
     * @PackageState(method="getPackageStateForGroupSize", message="The group is full.", groups={"group-join"})
     */
    private $group;

    /**
     * @var User
     */
    private $user;

    public function __construct(User $user, Group $group)
    {
        $this->fields = new ArrayCollection();
        $this->user = $user;
        $this->group = $group;
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function getFieldsIds()
    {
        if (!($this->fields instanceof ArrayCollection)) {
            return array();
        }

        return $this->fields->map(function ($fieldValue) {
                return $fieldValue->getField()->getId();
        })->toArray();
    }

    /**
     * @param mixed $passcode
     * @return Worksheet
     */
    public function setPasscode($passcode)
    {
        $this->passcode = $passcode;

        return $this;
    }

    public function getPasscode()
    {
        return $this->passcode;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
        if (!empty($this->fields)) {
            foreach ($this->fields as $field) {
                $field->setUser($user);
            }
        }
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    public function setGroup(Group $group)
    {
        $this->group = $group;
    }

    /**
     * @return Group
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param ExecutionContextInterface $context
     * @deprecated
     */
    public function isCorrectRequiredFields(ExecutionContextInterface $context)
    {
        if ($this->group->getFillFieldsRequired()) {
            $groupFieldsIds = $this->group->getFieldsIds();
            $userFieldsIds = $this->getFieldsIds();

            if (!empty(array_diff($groupFieldsIds, $userFieldsIds))) {
                $context->addViolationAt('fields', 'Please to fill required fields', array(), null);
            }
        }
    }

    public function isCorrectRequiredAnsweredFields(ExecutionContextInterface $context)
    {
        if ($this->group->getFillFieldsRequired()) {
            $groupFieldsIds = $this->group->getFieldsIds();
            $userFieldsIds = array_map(function (WorksheetField $field) {
                return $field->getId();
            }, $this->getAnsweredFields());

            if (!empty(array_diff($groupFieldsIds, $userFieldsIds))) {
                $context->addViolationAt('answered_fields', 'Please fill group\'s required fields.');
            }
        }
    }

    /**
     * @return WorksheetField[]
     */
    public function getAnsweredFields()
    {
        return $this->answeredFields;
    }

    /**
     * @param WorksheetField $answeredFields
     * @return Worksheet
     */
    public function setAnsweredFields($answeredFields)
    {
        $this->answeredFields = $answeredFields;

        return $this;
    }
}
