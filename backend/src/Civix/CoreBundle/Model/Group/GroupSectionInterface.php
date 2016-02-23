<?php

namespace Civix\CoreBundle\Model\Group;

use Civix\CoreBundle\Entity\GroupSection;
use Doctrine\Common\Collections\ArrayCollection;

interface GroupSectionInterface
{
    public function addGroupSection(GroupSection $section);
    public function removeGroupSection(GroupSection $section);

    /**
     * @return ArrayCollection
     */
    public function getGroupSections();
}
