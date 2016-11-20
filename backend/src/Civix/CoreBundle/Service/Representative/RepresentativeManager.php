<?php

namespace Civix\CoreBundle\Service\Representative;

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

    public function approveRepresentative(Representative $representative)
    {
        $representative->setStatus(Representative::STATUS_ACTIVE);

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
        $ciceroRepresentative = $this->ciceroService->findRepresentativeByNameAndId(
            $representative->getUser()->getFirstName(),
            $representative->getUser()->getLastName(),
            $representative->getCiceroId()
        );

        if ($ciceroRepresentative) {
            $this->ciceroStorageService
                ->fillRepresentativeByApiObj($representative, $ciceroRepresentative);
        } else {
            $representative->setDistrict(null);
            $representative->setCiceroId(null);
        }

        $this->entityManager->persist($representative);
        $this->entityManager->flush($representative);

        return !!$ciceroRepresentative;
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
