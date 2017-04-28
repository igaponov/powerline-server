<?php

namespace Civix\CoreBundle\Service;

use Civix\Component\ContentConverter\ConverterInterface;
use Civix\CoreBundle\Entity\CiceroRepresentative;
use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Entity\District;
use Civix\CoreBundle\Event\AvatarEvent;
use Civix\CoreBundle\Event\AvatarEvents;
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
     * @var CongressApi
     */
    private $congressService;
    /**
     * @var OpenstatesApi
     */
    private $openstatesService;
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
        CongressApi $congress,
        OpenstatesApi $openstates,
        ConverterInterface $converter,
        EventDispatcherInterface $dispatcher
    ) {
        $this->ciceroService = $ciceroService;
        $this->entityManager = $entityManager;
        $this->congressService = $congress;
        $this->openstatesService = $openstates;
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
    public function getRepresentativesByLocation($address, $city, $state, $country = 'US')
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
    public function getRepresentativesByOfficialInfo($firstName, $lastName, $officialTitle)
    {
        $representatives = $this->ciceroService
            ->findRepresentativeByOfficialData($firstName, $lastName, $officialTitle);

        return $this->handleOfficialResponse($representatives);
    }

    protected function handleOfficialResponse($officials)
    {
        foreach ($officials as &$representative) {
            $object = $this->entityManager->getRepository(CiceroRepresentative::class)
                ->find($representative->id);
            if ($object) {
                $representative = $this->fillRepresentativeByApiObj($object, $representative);
            } else {
                $representative = $this->createCiceroRepresentative($representative);
            }
            $event = new AvatarEvent($representative);
            $this->dispatcher->dispatch(AvatarEvents::CHANGE, $event);

            $this->entityManager->persist($representative);
        }
        $this->entityManager->flush();

        return $officials;
    }

    /**
     * Get representative from api, save his, get district id.
     *
     * @param Representative $representative Representative object
     *
     * @return bool
     */
    public function updateByRepresentativeInfo(Representative $representative)
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

    protected function createCiceroRepresentative($response)
    {
        return $this->fillRepresentativeByApiObj(new CiceroRepresentative(), $response);
    }

    /**
     * Save representative from api in representative storage. 
     * Set link between representative and representative storage.
     * 
     * @param array $resultApiCollection Object from Cicero API
     * @param CiceroRepresentative $representative CiceroRepresentative object
     *
     * @return bool
     */
    protected function updateRepresentative($resultApiCollection, CiceroRepresentative $representative)
    {
        foreach ($resultApiCollection as $repr) {
            if ($representative->getId() == $repr->id) {
                $representative = $this->fillRepresentativeByApiObj($representative, $repr);

                $event = new AvatarEvent($representative);
                $this->dispatcher->dispatch(AvatarEvents::CHANGE, $event);

                $this->entityManager->persist($representative);
                $this->entityManager->flush();
                return true;
            }
        }

        return false;
    }

    /**
     * Change Representative Storage object according to object which was getten from Cicero Api.
     * 
     * @param \Civix\CoreBundle\Entity\CiceroRepresentative $representative
     * @param object $response Cicero Api object
     * 
     * @return \Civix\CoreBundle\Entity\CiceroRepresentative
     */
    public function fillRepresentativeByApiObj(CiceroRepresentative $representative, $response)
    {
        $representative->setId($response->id);
        $representative->setFirstName(trim($response->first_name));
        $representative->setLastName(trim($response->last_name));
        $representative->setOfficialTitle(trim($response->office->title));
        if ($response->photo_origin_url) {
            $content = $this->converter->convert($response->photo_origin_url);
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
        foreach ($response->identifiers as $identificator) {
            $socialType = strtolower(isset($identificator->identifier_type) ? $identificator->identifier_type : '');
            $socialMethod = 'set'.ucfirst($socialType);
            if (method_exists($representative, $socialMethod)) {
                $representative->$socialMethod($identificator->identifier_value);
            }
        }

        //update profile from congress api
        $this->congressService->updateRepresentativeProfile($representative);

        //update profile from openstate api
        $this->openstatesService->updateRepresentativeProfile($representative);

        return $representative;
    }

    /**
     * Get districts of user. Save representative in storage if need.
     *
     * @param string $address   Address
     * @param string $city      City
     * @param string $state     State
     * @param string $country   Country
     *
     * @return array of districts
     */
    public function getUserDistrictsFromApi($address, $city, $state, $country = 'US')
    {
        $resultApiCollection = $this->ciceroService
            ->findRepresentativeByLocation($address, $city, $state, $country);
        $districts = array();
        foreach ($resultApiCollection as $response) {
            $districts[] = $this->createDistrict($response->office->district);
        }

        //add nonlegislative districts to current district
        $districts = array_merge($districts, $this->getNonlegislaveDistricts());

        $this->entityManager->flush();

        return array_unique($districts);
    }

    /**
     * Get nonlegilative districts with type CENSUS and subtype SUBDIVISION
     * by coordinats.
     *
     * @return array
     */
    protected function getNonlegislaveDistricts()
    {
        $subdivDistricts = array();

        $districts = $this->ciceroService->findNonLegislativeDistricts();

        foreach ($districts as $district) {
            if ($district->subtype == CiceroCalls::CENSUS_SUBTYPE) {
                //create local district
                $localDistrict = $this->createDistrict($district);
                $subdivDistricts[] = $localDistrict;
            }
        }

        return $subdivDistricts;
    }

    protected function createDistrict($district)
    {
        $currentDistrict = $this->entityManager->getRepository('CivixCoreBundle:District')
            ->find($district->id);
        if (!$currentDistrict) {
            $currentDistrict = new District();
            $currentDistrict->setId($district->id);
            $currentDistrict->setLabel($district->label);

            //set district type (for Nonlegislave district type = LOCAL)
            if ($district->district_type != 'CENSUS') {
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
