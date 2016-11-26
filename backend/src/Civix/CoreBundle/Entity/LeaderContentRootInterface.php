<?php
namespace Civix\CoreBundle\Entity;

interface LeaderContentRootInterface
{
    /**
     * @return integer
     */
    public function getId();

    /**
     * @return string
     */
    public function getType();

    /**
     * @return User
     */
    public function getUser();

    /**
     * @return string
     */
    public function getEmail();

    /**
     * @return string
     */
    public function getOfficialTitle();
}