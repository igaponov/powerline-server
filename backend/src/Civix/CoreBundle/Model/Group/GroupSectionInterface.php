<?php

namespace Civix\CoreBundle\Model\Group;

use Civix\CoreBundle\Entity\GroupSection;
use Doctrine\Common\Collections\ArrayCollection;

interface GroupSectionInterface
{
    /**
     * @param GroupSection $section
     * @return $this
     */
    public function addGroupSection(GroupSection $section);

    /**
     * @param GroupSection $section
     * @return $this
     */
    public function removeGroupSection(GroupSection $section);

    /**
     * @return ArrayCollection
     */
    public function getGroupSections();

    /**
     * @return array
     */
    public function getGroupSectionIds();
}
