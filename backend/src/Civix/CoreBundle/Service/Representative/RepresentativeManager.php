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
     * Generate representative username by name and set new username.
     *
     * @param Representative $representative
     * @param int            $iteration
     *
     * @return string New username
     */
    public function generateRepresentativeUsername(Representative $representative, $iteration = 0)
    {
        // Generate canonical name
        $name = $representative->getFirstName().$representative->getLastName();
        $name = preg_replace('/[^\w]/i', '', $name);
        $name = strtolower($name);
        $name = $iteration ? ($name.$iteration) : $name;

        $representByUsername = $this->entityManager
            ->getRepository('CivixCoreBundle:Representative')
            ->findOneBy(array('username' => $name));

        if (is_null($representByUsername)) {
            $representative->setUsername($name);
        } else {
            $name = $this->generateRepresentativeUsername($representative, ++$iteration);
        }

        return $name;
    }

    /**
     * Generate representative password and set to representative.
     *
     * @param Representative $representative
     *
     * @return string New password
     */
    public function generateRepresentativePassword(Representative $representative)
    {
        $newPassword = substr(base_convert(sha1(uniqid(mt_rand(), true)), 16, 36), 0, 9);

        $encoder = $this->encoderFactory->getEncoder($representative);
        $password = $encoder->encodePassword($newPassword, $representative->getSalt());
        $representative->setPassword($password);

        return $newPassword;
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
            $representative->getFirstName(),
            $representative->getLastName(),
            $representative->getCiceroId()
        );

        if ($ciceroRepresentative) {
            //update current data of representative from cicero
            $this->ciceroStorageService->fillRepresentativeByApiObj($representative, $ciceroRepresentative);
            $this->entityManager->persist($representative);

            //update representative in civix
            if ($representative instanceof Representative) {
                $representative->setOfficialTitle($representative->getOfficialTitle());
                $representative->setDistrict($representative->getDistrict());
                $this->entityManager->persist($representative);
                $this->entityManager->flush($representative);
            }
        } else {
            //unlink district and storage
            if ($representative instanceof Representative) {
                $representative->setDistrict(null);
                $representative->setCiceroId(null);
                $this->entityManager->persist($representative);
                $this->entityManager->flush($representative);
            }

            return false;
        }

        return true;
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
