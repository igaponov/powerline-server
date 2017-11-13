<?php

namespace Civix\CoreBundle\Service;

use Civix\Component\ContentConverter\ConverterInterface;
use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Entity\District;
use Civix\CoreBundle\Entity\State;
use Civix\CoreBundle\Model\TempFile;
use Civix\CoreBundle\Repository\DistrictRepository;
use Civix\CoreBundle\Repository\StateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\File;

class RepresentativeImporter
{
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var StateRepository
     */
    private $stateRepository;
    /**
     * @var DistrictRepository
     */
    private $districtRepository;
    /**
     * @var ConverterInterface
     */
    private $converter;

    public function __construct(
        EntityManagerInterface $em,
        StateRepository $stateRepository,
        DistrictRepository $districtRepository,
        ConverterInterface $converter
    ) {
        $this->em = $em;
        $this->stateRepository = $stateRepository;
        $this->districtRepository = $districtRepository;
        $this->converter = $converter;
    }

    public function import(File $fileObject): void
    {
        $file = new \SplFileObject($fileObject->getRealPath());
        $file->setFlags(\SplFileObject::DROP_NEW_LINE|\SplFileObject::SKIP_EMPTY);
        while ($row = $file->fgetcsv()) {
            $representative = (new Representative())
                ->setFirstName($row[0])
                ->setLastName($row[1])
                ->setOfficialTitle($row[2])
                ->setPhone($row[3])
                ->setFax($row[4])
                ->setEmail($row[5])
                ->setWebsite($row[6])
                ->setCountry($row[7])
                // state
                ->setCity($row[9])
                ->setAddressLine1($row[10])
                ->setAddressLine2($row[11])
                ->setAddressLine3($row[12])
                // district
                ->setParty($row[14])
                ->setBirthday(new \DateTime($row[15]))
                ->setStartTerm(new \DateTime($row[16]))
                ->setEndTerm(new \DateTime($row[17]))
                ->setContactForm($row[18])
                ->setMissedVotes($row[19])
                ->setVotesWithParty($row[20])
                ->setFacebook($row[21])
                ->setYoutube($row[22])
                ->setTwitter($row[23])
                ->setBioguide($row[24]);
            /** @var State $state */
            $state = $this->stateRepository->findOneBy(['code' => $row[8]]);
            if ($state) {
                $representative->setState($state);
            }
            /** @var District $district */
            $district = $this->districtRepository->findOneBy(['label' => $row[13]]);
            if ($district) {
                $representative->setDistrict($district);
            }
            $avatar = $this->converter->convert($row[25]);
            if ($avatar) {
                $avatar = new TempFile($avatar);
                $representative->setAvatar($avatar);
            }

            $this->em->persist($representative);
        }
        $this->em->flush();
    }
}