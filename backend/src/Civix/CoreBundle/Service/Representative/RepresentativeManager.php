<?php

namespace Civix\CoreBundle\Service\Representative;

use Civix\CoreBundle\Entity\CiceroRepresentative;
use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Service\CiceroApi;
use Civix\CoreBundle\Service\CiceroCalls;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;

class RepresentativeManager
{
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var EncoderFactory
     */
    private $encoderFactory;
    /**
     * @var CiceroApi
     */
    private $ciceroStorageService;
    /**
     * @var CiceroCalls
     */
    private $ciceroService;

    public function __construct(
        EntityManager $entityManager,
        EncoderFactory $encoder,
        CiceroApi $ciceroStorageService,
        CiceroCalls $ciceroService
    ) {
        $this->entityManager = $entityManager;
        $this->encoderFactory = $encoder;
        $this->ciceroStorageService = $ciceroStorageService;
        $this->ciceroService = $ciceroService;
    }

    public function save(Representative $representative)
    {
        $this->entityManager->persist($representative);
        $this->entityManager->flush();

        return $representative;
    }

    public function approveRepresentative(Representative $representative)
    {
        $representative->setStatus(Representative::STATUS_ACTIVE);

        $this->synchronizeRepresentative($representative);

        return $representative;
    }

    /**
     * Synchronize $storageRepresentative with Cicero representative.
     *
     * @param \Civix\CoreBundle\Entity\Representative $representative
     *
     * @return bool
     */
    public function synchronizeRepresentative(Representative $representative)
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
            $representatives = $this->ciceroStorageService->getRepresentativesByOfficialInfo(
                $representative->getUser()->getFirstName(),
                $representative->getUser()->getLastName(),
                $representative->getOfficialTitle()
            );
            if (!$representatives) {
                //if no representative in cicero api
                //try to get info by address
                $representatives = $this->ciceroStorageService
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
            $representative->setCiceroRepresentative(null);
        }

        $this->entityManager->persist($representative);
        $this->entityManager->flush($representative);

        return (bool)$ciceroRepresentative;
    }

    public function synchronizeByStateCode($stateCode)
    {
        $representatives = $this->entityManager->getRepository(Representative::class)
            ->findByState($stateCode);
        foreach ($representatives as $storageRepresentative) {
            $this->synchronizeRepresentative($storageRepresentative);
        }
    }
}
