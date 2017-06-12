<?php

namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Event\UserEvent;
use Civix\CoreBundle\Event\UserEvents;
use Civix\CoreBundle\Repository\GroupRepository;
use Civix\CoreBundle\Service\Group\GroupManager;
use Doctrine\ORM\EntityManagerInterface;
use Geocoder\Exception\Exception;
use Geocoder\Geocoder;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserLocalGroupSubscriber implements EventSubscriberInterface
{
    const EU_CODES = [
        'AT',
        'BE',
        'BG',
        'HR',
        'CY',
        'CZ',
        'DK',
        'EE',
        'FI',
        'FR',
        'DE',
        'GR',
        'HU',
        'IE',
        'IT',
        'LV',
        'LT',
        'LU',
        'MT',
        'NL',
        'PO',
        'PT',
        'RO',
        'SK',
        'SI',
        'ES',
        'SE',
    ];

    const AU_CODES = [
        'DZ',
        'AO',
        'BJ',
        'BW',
        'BF',
        'BI',
        'CM',
        'CV',
        'CF',
        'TD',
        'KM',
        'CD',
        'DJ',
        'EG',
        'GQ',
        'ER',
        'ET',
        'GA',
        'GM',
        'GH',
        'GN',
        'GW',
        'CI',
        'KE',
        'LS',
        'LR',
        'LY',
        'MG',
        'MW',
        'ML',
        'MR',
        'MU',
        'MA',
        'MZ',
        'NA',
        'NE',
        'NG',
        'CG',
        'RW',
        'ST',
        'SN',
        'SC',
        'SL',
        'SO',
        'ZA',
        'SS',
        'SD',
        'SZ',
        'TZ',
        'TG',
        'TN',
        'UG',
        'EH',
        'ZM',
        'ZW',
    ];

    /**
     * @var Geocoder
     */
    private $geocoder;
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var GroupRepository
     */
    private $groupRepository;
    /**
     * @var GroupManager
     */
    private $manager;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public static function getSubscribedEvents(): array
    {
        return [
            UserEvents::REGISTRATION => 'joinLocalGroups',
            UserEvents::ADDRESS_CHANGE => 'joinLocalGroups',
        ];
    }

    public function __construct(
        Geocoder $geocoder,
        EntityManagerInterface $em,
        GroupRepository $groupRepository,
        GroupManager $manager,
        LoggerInterface $logger
    ) {
        $this->geocoder = $geocoder;
        $this->em = $em;
        $this->groupRepository = $groupRepository;
        $this->manager = $manager;
        $this->logger = $logger;
    }

    public function joinLocalGroups(UserEvent $event): void
    {
        $user = $event->getUser();
        $oldGroups = $this->groupRepository->getGeoGroupsByUser($user);

        $query = $user->getAddressQuery();
        try {
            $collection = $this->geocoder->geocode($query);
        } catch (Exception $e) {
            $this->logger->critical('Geocoder error has occurred.', [
                'exception' => $e,
                'query' => $query,
            ]);
            return;
        }

        $address = $collection->first();
        $country = $address->getCountry();
        $adminLevel = $address->getAdminLevels()->first();
        $locality = $address->getLocality() ? : $address->getSubLocality();
        $groupConfig = [];
        if (($country && in_array($country->getCode(), self::EU_CODES, true))
            || in_array($user->getCountry(), self::EU_CODES, true)) {
            $groupConfig[] = [null, Group::GROUP_TYPE_COUNTRY, Group::GROUP_LOCATION_NAME_EUROPEAN_UNION, 'European Union'];
        } elseif (($country && in_array($country->getCode(), self::AU_CODES, true))
            || in_array($user->getCountry(), self::AU_CODES, true)) {
            $groupConfig[] = [null, Group::GROUP_TYPE_COUNTRY, Group::GROUP_LOCATION_NAME_AFRICAN_UNION, 'African Union'];
        }
        if ($country) {
            $groupConfig[] = [$user->getCountry(), Group::GROUP_TYPE_COUNTRY, $country ? $country->getCode() : null, $country ? $country->getName() : null];
        }
        if ($adminLevel) {
            $groupConfig[] = [$user->getState(), Group::GROUP_TYPE_STATE, $adminLevel ? $adminLevel->getCode() : null, $adminLevel ? $adminLevel->getName() : null];
        }
        if ($locality) {
            $groupConfig[] = [$user->getCity(), Group::GROUP_TYPE_LOCAL, $locality, $locality];
        }

        $newGroups = $this->getNewGroups($groupConfig);

        /** @var Group[] $remove */
        $remove = array_diff_key($oldGroups, $newGroups);
        /** @var Group[] $join */
        $join = array_diff_key($newGroups, $oldGroups);
        foreach ($remove as $item) {
            $this->em->remove($item->getUserGroups()->first());
        }
        $this->em->flush();
        foreach ($join as $item) {
            $this->manager->joinToGroup($user, $item);
        }
    }

    /**
     * @param int $type Group's type
     * @param string $longName
     * @param string $shortName
     * @param Group|null $parentGroup
     * @return Group
     */
    private function createLocalGroup(int $type, string $longName, string $shortName, ?Group $parentGroup): Group
    {
        $group = new Group();
        $group
            ->setGroupType($type)
            ->setOfficialName($longName)
            ->setLocationName($shortName)
            ->setParent($parentGroup)
        ;

        $this->em->persist($group);

        return $group;
    }

    /**
     * @param $groupConfig
     * @return array
     */
    private function getNewGroups(array $groupConfig): array
    {
        $newGroups = [];
        $parent = null;
        foreach ($groupConfig as [$fallback, $type, $shortName, $longName]) {
            if ($shortName) {
                $localGroup = $this->groupRepository->findOneBy(
                    [
                        'locationName' => $shortName,
                        'groupType' => $type,
                        'parent' => $parent,
                    ]
                );
                if (!$localGroup) {
                    $localGroup = $this->createLocalGroup($type, $longName, $shortName, $parent);
                }
            } else {
                $localGroup = $this->groupRepository->findOneBy(
                    [
                        'locationName' => $fallback,
                        'groupType' => $type,
                        'parent' => $parent,
                    ]
                );
            }
            if ($localGroup) {
                $parent = $localGroup;
                $newGroups[$localGroup->getId() ? : uniqid('', true)] = $localGroup;
            } else {
                break;
            }
        }

        return $newGroups;
    }
}