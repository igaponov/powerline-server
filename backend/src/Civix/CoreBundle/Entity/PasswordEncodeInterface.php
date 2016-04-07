<?php
namespace Civix\CoreBundle\Entity;

interface PasswordEncodeInterface
{
    /**
     * @param string $password
     * @return $this
     */
    public function setPassword($password);

    /**
     * @param string $password
     * @return $this
     */
    public function setPlainPassword($password);

    /**
     * @return string|null
     */
    public function getPlainPassword();

    /**
     * @return string
     */
    public function getSalt();
}