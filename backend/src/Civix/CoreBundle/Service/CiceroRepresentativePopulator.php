<?php

namespace Civix\CoreBundle\Service;

use Civix\Component\ContentConverter\ConverterInterface;
use Civix\CoreBundle\Entity\CiceroRepresentative;
use Civix\CoreBundle\Entity\District;
use Civix\CoreBundle\Model\TempFile;
use Civix\CoreBundle\Repository\DistrictRepository;
use Civix\CoreBundle\Repository\StateRepository;

class CiceroRepresentativePopulator
{
    /**
     * @var ConverterInterface
     */
    private $converter;
    /**
     * @var StateRepository
     */
    private $stateRepository;
    /**
     * @var DistrictRepository
     */
    private $districtRepository;

    public function __construct(
        ConverterInterface $converter,
        StateRepository $stateRepository,
        DistrictRepository $districtRepository
    ) {
        $this->converter = $converter;
        $this->stateRepository = $stateRepository;
        $this->districtRepository = $districtRepository;
    }

    /**
     * Change CiceroRepresentative object
     * according to object which was got from Cicero Api.
     *
     * @param CiceroRepresentative $representative
     * @param \stdClass $response Cicero Api object
     */
    public function fillRepresentativeByApiObj(CiceroRepresentative $representative, $response): void
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
        $district = $this->createDistrict($response->office->district);
        $representative->setDistrict($district);

        $representative->setEmail($response->office->chamber->contact_email);
        $representative->setWebsite($response->office->chamber->url);
        $representative->setCountry($response->office->district->country);
        if (isset($response->addresses[0])) {
            $representative->setPhone($response->addresses[0]->phone_1);
            $representative->setFax($response->addresses[0]->fax_1);
            $state = $this->stateRepository
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
    }

    protected function createDistrict($district)
    {
        $currentDistrict = $this->districtRepository->find($district->id);
        if (!$currentDistrict) {
            $currentDistrict = new District();
            $currentDistrict->setId($district->id);
            $currentDistrict->setLabel($district->label);

            // set district type (for Nonlegislative district type = LOCAL)
            if ($district->district_type !== 'CENSUS') {
                $currentDistrict->setDistrictType(
                    constant('Civix\CoreBundle\Entity\District::'.$district->district_type)
                );
            } else {
                $currentDistrict->setDistrictType(District::LOCAL);
            }
        }

        return $currentDistrict;
    }
}