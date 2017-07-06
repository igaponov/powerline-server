<?php

namespace Civix\CoreBundle\Service;

use Civix\Component\ContentConverter\ConverterInterface;
use Civix\CoreBundle\Entity\CiceroRepresentative;
use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Entity\District;
use Civix\CoreBundle\Event\AvatarEvent;
use Civix\CoreBundle\Event\AvatarEvents;
use Civix\CoreBundle\Event\CiceroRepresentativeEvent;
use Civix\CoreBundle\Event\CiceroRepresentativeEvents;
use Civix\CoreBundle\Model\TempFile;
use Civix\CoreBundle\Service\API\ServiceApi;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CiceroApi extends ServiceApi
{
    /**
     * @var CiceroCalls
     */
    private $ciceroService;
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var ConverterInterface
     */
    private $converter;
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(
        CiceroCalls $ciceroService,
        EntityManager $entityManager,
        ConverterInterface $converter,
        EventDispatcherInterface $dispatcher
    ) {
        $this->ciceroService = $ciceroService;
        $this->entityManager = $entityManager;
        $this->converter = $converter;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Get all representatives by address from api, save them, get districs ids.
     *
     * @param string $address   Address
     * @param string $city      City
     * @param string $state     State
     * @param string $country   Country
     *
     * @return CiceroRepresentative[]
     */
    public function getRepresentativesByLocation($address, $city, $state, $country = 'US'): array
    {
        $representatives = $this->ciceroService
            ->findRepresentativeByLocation($address, $city, $state, $country);

        return $this->handleOfficialResponse($representatives);
    }

    /**
     * @param string $firstName
     * @param string $lastName
     * @param string $officialTitle
     *
     * @return CiceroRepresentative[]
     */
    public function getRepresentativesByOfficialInfo($firstName, $lastName, $officialTitle): array
    {
        $representatives = $this->ciceroService
            ->findRepresentativeByOfficialData($firstName, $lastName, $officialTitle);

        return $this->handleOfficialResponse($representatives);
    }

    protected function handleOfficialResponse(array $officials): array
    {
        $representatives = [];
        foreach ($officials as $representative) {
            $repository = $this->entityManager->getRepository(CiceroRepresentative::class);
            $object = $repository->find($representative->id);
            if (!$object) {
                $object = $repository->findOneBy([
                    'district' => $representative->office->district->id,
                    'firstName' => $representative->first_name,
                    'lastName' => $representative->last_name,
                ]);
            }
            if ($object) {
                $representative = $this->fillRepresentativeByApiObj($object, $representative);
            } else {
                $representative = $this->createCiceroRepresentative($representative);
            }
            $event = new AvatarEvent($representative);
            $this->dispatcher->dispatch(AvatarEvents::CHANGE, $event);

            $this->entityManager->persist($representative);
            $representatives[] = $representative;
        }
        $this->entityManager->flush();

        return $representatives;
    }

    /**
     * Get representative from api, save his, get district id.
     *
     * @param Representative $representative Representative object
     *
     * @return bool
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     */
    public function updateByRepresentativeInfo(Representative $representative): bool
    {
        $representativesFromApi = $this->ciceroService
            ->findRepresentativeByOfficialData(
                $representative->getUser()->getFirstName(),
                $representative->getUser()->getLastName(),
                $representative->getOfficialTitle()
            );
        if ($representativesFromApi) {
            return $this->updateRepresentative(
                $representativesFromApi,
                $representative->getCiceroRepresentative()
            );
        }

        return false;
    }

    /**
     * Synchronize $storageRepresentative with Cicero representative.
     *
     * @param \Civix\CoreBundle\Entity\Representative $representative
     *
     * @return bool
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     */
    public function synchronizeRepresentative(Representative $representative): bool
    {
        //find in current representative storage
        $ciceroRepresentative = $this->entityManager
            ->getRepository(CiceroRepresentative::class)
            ->getByOfficialInfo(
                $representative->getUser()->getFirstName(),
                $representative->getUser()->getLastName(),
                $representative->getOfficialTitle()
            );

        if (!$ciceroRepresentative) {
            $representatives = $this->getRepresentativesByOfficialInfo(
                $representative->getUser()->getFirstName(),
                $representative->getUser()->getLastName(),
                $representative->getOfficialTitle()
            );
            if (!$representatives) {
                //if no representative in cicero api
                //try to get info by address
                $representatives = $this
                    ->getRepresentativesByLocation(
                        $representative->getAddress(),
                        $representative->getCity(),
                        $representative->getStateCode(),
                        $representative->getCountry()
                    );
                if ($representatives) {
                    $representative->setIsNonLegislative(1);
                    $representative->setDistrict($representatives[0]->getDistrict());
                }
            } else {
                $ciceroRepresentative = $representatives[0];
            }
        }

        if ($ciceroRepresentative) {
            $representative->setDistrict($ciceroRepresentative->getDistrict());
            $representative->setCiceroRepresentative($ciceroRepresentative);
        } else {
            $representative->setDistrict(null);
            $representative->setCiceroRepresentative();
        }

        $this->entityManager->persist($representative);
        $this->entityManager->flush();

        return (bool)$ciceroRepresentative;
    }

    public function synchronizeByStateCode($stateCode): void
    {
        $representatives = $this->entityManager->getRepository(Representative::class)
            ->findByState($stateCode);
        foreach ($representatives as $storageRepresentative) {
            $this->synchronizeRepresentative($storageRepresentative);
        }
    }

    protected function createCiceroRepresentative($response): CiceroRepresentative
    {
        return $this->fillRepresentativeByApiObj(new CiceroRepresentative(), $response);
    }

    /**
     * Save representative from api in representative storage.
     * Set link between representative and representative storage.
     *
     * @param array $apiCollection Object from Cicero API
     * @param CiceroRepresentative $representative CiceroRepresentative object
     *
     * @return bool
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     */
    protected function updateRepresentative($apiCollection, CiceroRepresentative $representative): bool
    {
        $collection = array_filter($apiCollection, function($repr) use ($representative) {
            return $representative->getId() === $repr->id;
        });
        if (!$collection) {
            $collection = array_filter($apiCollection, function ($repr) use ($representative) {
                return $representative->getDistrict()->getId() === $repr->office->district->id
                    && $representative->getFirstName() === $repr->first_name
                    && $representative->getLastName() === $repr->last_name;
            });
        }
        if ($collection) {
            $representative = $this->fillRepresentativeByApiObj($representative, reset($collection));

            $event = new AvatarEvent($representative);
            $this->dispatcher->dispatch(AvatarEvents::CHANGE, $event);

            $this->entityManager->persist($representative);
            $this->entityManager->flush();

            return true;
        }

        return false;
    }

    /**
     * Change Representative Storage object according to object which was getten from Cicero Api.
     * 
     * @param CiceroRepresentative $representative
     * @param \stdClass $response Cicero Api object
     * 
     * @return CiceroRepresentative
     */
    private function fillRepresentativeByApiObj(CiceroRepresentative $representative, $response): CiceroRepresentative
    {
        $representative->setId($response->id);
        $representative->setFirstName(trim($response->first_name));
        $representative->setLastName(trim($response->last_name));
        $representative->setOfficialTitle(trim($response->office->title));
        if ($response->photo_origin_url) {
            $content = $this->converter->convert((string)$response->photo_origin_url);
            $representative->setAvatar(new TempFile($content));
        }

        //create district
        $representativeDistrict = $this->createDistrict($response->office->district);
        $representative->setDistrict($representativeDistrict);

        $representative->setEmail($response->office->chamber->contact_email);
        $representative->setWebsite($response->office->chamber->url);
        $representative->setCountry($response->office->district->country);
        if (isset($response->addresses[0])) {
            $representative->setPhone($response->addresses[0]->phone_1);
            $representative->setFax($response->addresses[0]->fax_1);
            $state = $this->entityManager->getRepository('CivixCoreBundle:State')
                ->findOneBy(['code' => $response->addresses[0]->state]);
            $representative->setState($state);
            $representative->setCity($response->addresses[0]->city);
            $representative->setAddressLine1($response->addresses[0]->address_1);
            $representative->setAddressLine2($response->addresses[0]->address_2);
            $representative->setAddressLine3($response->addresses[0]->address_3);
        }

        //extended profile
        $representative->setParty($response->party);
        if (isset($response->notes[1]) && \DateTime::createFromFormat('Y-m-d', $response->notes[1]) !== false) {
            $representative->setBirthday(new \DateTime($response->notes[1]));
        }
        if (isset($response->current_term_start_date)) {
            $representative->setStartTerm(new \DateTime($response->current_term_start_date));
        }
        if (isset($response->term_end_date)) {
            $representative->setEndTerm(new \DateTime($response->term_end_date));
        }

        //social networks
        /** @var array $identifiers */
        $identifiers = $response->identifiers;
        foreach ($identifiers as $identificator) {
            $socialType = strtolower($identificator->identifier_type ?? '');
            $socialMethod = 'set'.ucfirst($socialType);
            if (method_exists($representative, $socialMethod)) {
                $representative->$socialMethod($identificator->identifier_value);
            }
        }

        $event = new CiceroRepresentativeEvent($representative);
        $this->dispatcher->dispatch(CiceroRepresentativeEvents::UPDATE, $event);

        return $representative;
    }

    protected function createDistrict($district)
    {
        $currentDistrict = $this->entityManager->getRepository('CivixCoreBundle:District')
            ->find($district->id);
        if (!$currentDistrict) {
            $currentDistrict = new District();
            $currentDistrict->setId($district->id);
            $currentDistrict->setLabel($district->label);

            //set district type (for Nonlegislative district type = LOCAL)
            if ($district->district_type !== 'CENSUS') {
                $currentDistrict->setDistrictType(
                    constant('Civix\CoreBundle\Entity\District::'.
                        $district->district_type)
                );
            } else {
                $currentDistrict->setDistrictType(District::LOCAL);
            }

            $this->entityManager->persist($currentDistrict);
            $this->entityManager->flush($currentDistrict);
        }

        return $currentDistrict;
    }
}
