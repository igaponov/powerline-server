<?php

namespace Civix\CoreBundle\Model\Group;

use Civix\CoreBundle\Entity\GroupSection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

trait GroupSectionTrait
{
    /**
     * @var ArrayCollection|GroupSection[]
     * @ORM\ManyToMany(targetEntity="\Civix\CoreBundle\Entity\GroupSection", cascade={"persist"})
     */
    protected $groupSections;

    /**
     * Add group section.
     *
     * @param GroupSection $section
     *
     * @return $this
     */
    public function addGroupSection(GroupSection $section)
    {
        if (!$this->groupSections->contains($section)) {
            $this->groupSections[] = $section;
        }

        return $this;
    }

    /**
     * Remove section.
     *
     * @param GroupSection $section
     */
    public function removeGroupSection(GroupSection $section)
    {
        $this->groupSections->removeElement($section);
    }

    public function getGroupSections()
    {
        return $this->groupSections;
    }

    public function setGroupSections($groupSections)
    {
        $this->groupSections = new ArrayCollection($groupSections);
    }

    public function getGroupSectionIds()
    {
        return $this->groupSections
            ->map(function (GroupSection $section) {
                return $section->getId();
            })
            ->toArray();
    }
}
