<?php

namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\Report\UserReport;
use Civix\CoreBundle\Event\UserEvent;
use Civix\CoreBundle\Event\UserEvents;
use Civix\CoreBundle\Service\CiceroApi;
use Civix\CoreBundle\Service\User\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CiceroSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var UserManager
     */
    private $ciceroApi;

    public static function getSubscribedEvents(): array
    {
        return [
            UserEvents::REGISTRATION => 'updateDistrictsIds',
        ];
    }

    public function __construct(EntityManagerInterface $em, CiceroApi $ciceroApi)
    {
        $this->em = $em;
        $this->ciceroApi = $ciceroApi;
    }

    public function updateDistrictsIds(UserEvent $event): void
    {
        $user = $event->getUser();
        $representatives = $this->ciceroApi->getRepresentativesByLocation(
            $user->getLineAddress(),
            $user->getCity(),
            $user->getState(),
            $user->getCountry()
        );
        if (!empty($representatives)) {
            $user->getDistricts()->clear();

            $representativeList = $districtList = [];
            foreach ($representatives as $representative) {
                $representativeList[] = $representative->getOfficialTitle().' '.$representative->getFullName();
                $districtList[] = $representative->getDistrict()->getLabel();
                $user->addDistrict($representative->getDistrict());
            }
            $this->em->getRepository(UserReport::class)
                ->upsertUserReport($user, $user->getFollowers()->count(), $representativeList, null, null, null, array_unique($districtList));
            $user->setUpdateProfileAt(new \DateTime());
        }
    }
}