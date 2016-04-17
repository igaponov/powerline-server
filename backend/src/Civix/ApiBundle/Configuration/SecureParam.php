<?php

namespace Civix\ApiBundle\Configuration;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationAnnotation;

/**
 * SecureParam annotation
 * @Annotation
 */
class SecureParam extends ConfigurationAnnotation
{
    const NAME = 'secureparam';

    /**
     * @var object Entity to check permissions for
     */
    private $entity;

    /**
     * @var string Permission to check (current user in security context should have access to entity)
     */
    private $permission;

    /**
     * Returns the alias name for an annotated configuration.
     *
     * @return string
     */
    public function getAliasName()
    {
        return self::NAME;
    }

    /**
     * Returns whether multiple annotations of this type are allowed
     *
     * @return bool
     */
    public function allowArray()
    {
        return true;
    }

    public function setValue($entity)
    {
        $this->entity = $entity;
    }

    /**
     * @param object $entity
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
    }

    /**
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param string $permission
     */
    public function setPermission($permission)
    {
        $this->permission = $permission;
    }

    /**
     * @return string
     */
    public function getPermission()
    {
        return $this->permission;
    }
}