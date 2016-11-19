<?php

namespace Civix\CoreBundle\Service;

use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Entity\District;
use Civix\CoreBundle\Service\API\ServiceApi;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\KernelInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class CiceroApi extends ServiceApi
{
    /**
     * @var CiceroCalls
     */
    private $ciceroService;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var CropImage
     */
    private $cropImageService;
    /**
     * @var CongressApi
     */
    private $congressService;
    /**
     * @var OpenstatesApi
     */
    private $openstatesService;
    /**
     * @var UploaderHelper
     */
    protected $vichService;
    /**
     * @var string
     */
    protected $rootPath;

    public function __construct(
        CiceroCalls $ciceroService,
        Logger $logger,
        EntityManager $entityManager,
        UploaderHelper $vichUploader,
        CropImage $cropImage,
        KernelInterface $kernel,
        CongressApi $congress,
        OpenstatesApi $openstates
    ) {
        $this->ciceroService = $ciceroService;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->setCropImage($cropImage);
        $this->setVichService($vichUploader);
        $this->rootPath = $kernel->getRootDir().'/../web';
        $this->congressService = $congress;
        $this->openstatesService = $openstates;
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
            return $this->updateRepresentative($representativesFromApi, $representative);
        }

        return false;
    }

    /**
     * Save representative from api in representative storage. 
     * Set link between representative and representative storage.
     * 
     * @param array          $resultApiCollection Object from Cicero API
     * @param Representative $representative      Representative object
     *
     * @return bool
     */
    protected function updateRepresentative($resultApiCollection, Representative $representative)
    {
        foreach ($resultApiCollection as $repr) {
            if ($representative->getCiceroId() == $repr->id) {
                $representative = $this->fillRepresentativeByApiObj($representative, $repr);
                $this->entityManager->persist($representative);
                $this->entityManager->flush();
                return true;
            }
        }

        return false;
    }

    public function setEntityManager($entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function setCropImage($cropImage)
    {
        $this->cropImageService = $cropImage;
    }

    public function setVichService($vichService)
    {
        $this->vichService = $vichService;
    }

    public function setCongressApi($congressService)
    {
        $this->congressService = $congressService;
    }

    public function setOpenstatesApi($openstatesService)
    {
        $this->openstatesService = $openstatesService;
    }

    public function setCiceroCalls($ciceroService)
    {
        $this->ciceroService = $ciceroService;
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * Change Representative Storage object according to object which was getten from Cicero Api.
     * 
     * @param \Civix\CoreBundle\Entity\Representative $representative
     * @param object $response Cicero Api object
     * 
     * @return \Civix\CoreBundle\Entity\Representative
     */
    public function fillRepresentativeByApiObj(Representative $representative, $response)
    {
        $representative->setCiceroId($response->id);
        $representative->setOfficialTitle(trim($response->office->title));
        $representative->setAvatarSourceFileName($response->photo_origin_url);

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

        //save photo
        if (!empty($response->photo_origin_url)) {
            $fileInfo = explode('.', basename($response->photo_origin_url));
            $fileExt = array_pop($fileInfo);
            $representative->setAvatarFileName(uniqid().'.'.$fileExt);

            if (false !== ($header = $this->checkLink($response->photo_origin_url))) {
                if (strpos($header, 'image') !== false) {
                    //square avatars
                    try {
                        $temp_file = tempnam(sys_get_temp_dir(), 'avatar').'.'.$fileExt;
                        $this->saveImageFromUrl($representative->getAvatarSourceFileName(), $temp_file);
                        $this->cropImageService->rebuildImage(
                            $temp_file,
                            $temp_file
                        );
                        $fileUpload = new UploadedFile($temp_file, $representative->getAvatarFileName());
                        $representative->setAvatar($fileUpload);
                    } catch (\Exception $exc) {
                        $this->logger->addError('Image '.$representative->getAvatarSourceFileName().'. '.$exc->getMessage());
                        $representative->setAvatarSourceFileName(null);
                    }
                }
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
