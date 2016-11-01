<?php
namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Event\GroupEvent;
use Civix\CoreBundle\Event\GroupEvents;
use Civix\CoreBundle\Event\InquiryEvent;
use Civix\CoreBundle\Repository\UserGroupRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class GroupEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var UserGroupRepository
     */
    private $userGroupRepository;
    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(
        UserGroupRepository $userGroupRepository,
        EntityManager $em
    )
    {
        $this->userGroupRepository = $userGroupRepository;
        $this->em = $em;
    }

    public static function getSubscribedEvents()
    {
        return [
            GroupEvents::MEMBERSHIP_CONTROL_CHANGED => 'setApprovedAllUsersInGroup',
            GroupEvents::USER_INQUIRED => 'setAnsweredFields',
        ];
    }

    public function setApprovedAllUsersInGroup(GroupEvent $event)
    {
        $group = $event->getGroup();
        if ($group->getMembershipControl() == Group::GROUP_MEMBERSHIP_PUBLIC) {
            $this->userGroupRepository->setApprovedAllUsersInGroup($group);
        }
    }

    public function setAnsweredFields(InquiryEvent $event)
    {
        $worksheet = $event->getWorksheet();
        $user = $worksheet->getUser();
        $group = $worksheet->getGroup();

        //save fields values
        if ($group->getFillFieldsRequired()) {
            foreach ($worksheet->getAnsweredFields() as $field) {
                $groupField = $this->em->getRepository(Group\GroupField::class)->find($field->getId());
                $fieldValue = new Group\FieldValue();
                $fieldValue->setField($groupField);
                $fieldValue->setFieldValue($field->getValue());
                $fieldValue->setUser($user);
                $this->em->persist($fieldValue);
            }
        }

        $this->em->flush();
    }
}