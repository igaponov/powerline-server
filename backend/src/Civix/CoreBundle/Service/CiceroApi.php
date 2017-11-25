<?php

namespace Civix\CoreBundle\Service;

use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Entity\UserRepresentative;
use Civix\CoreBundle\Event\AvatarEvent;
use Civix\CoreBundle\Event\AvatarEvents;
use Civix\CoreBundle\Event\RepresentativeEvent;
use Civix\CoreBundle\Event\RepresentativeEvents;
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
     * @var EventDispatcherInterface
     */
    private $dispatcher;
    /**
     * @var CiceroRepresentativePopulator
     */
    private $populator;

    public function __construct(
        CiceroCalls $ciceroService,
        EntityManager $entityManager,
        EventDispatcherInterface $dispatcher,
        CiceroRepresentativePopulator $populator
    ) {
        $this->ciceroService = $ciceroService;
        $this->entityManager = $entityManager;
        $this->dispatcher = $dispatcher;
        $this->populator = $populator;
    }

    /**
     * Get all representatives by address from api, save them, get districs ids.
     *
     * @param string $address Address
     * @param string $city City
     * @param string $state State
     * @param string $country Country
     * @param null $zip
     *
     * @return Representative[]
     */
    public function getRepresentativesByLocation($address, $city, $state, $country = 'US', $zip = null): array
    {
        if ($country !== 'US') {
            return [];
        }
        $representatives = $this->ciceroService
            ->findRepresentativeByLocation($address, $city, $state, $country, $zip);

        return $this->handleOfficialResponse($representatives);
    }

    /**
     * @param string $firstName
     * @param string $lastName
     * @param string $officialTitle
     *
     * @return Representative[]
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
        foreach ($officials as $response) {
            $repository = $this->entityManager->getRepository(Representative::class);
            $representative = $repository->find($response->id);
            if (!$representative) {
                $representative = $repository->findOneBy([
                    'district' => $response->office->district->id,
                    'firstName' => $response->first_name,
                    'lastName' => $response->last_name,
                ]);
            }
            if ($representative) {
                $this->populator->populate($representative, $response);
            } else {
                $representative = $this->createRepresentative($response);
            }
            $event = new AvatarEvent($representative);
            $this->dispatcher->dispatch(AvatarEvents::CHANGE, $event);

            $event = new RepresentativeEvent($representative);
            $this->dispatcher->dispatch(RepresentativeEvents::UPDATE, $event);

            $this->entityManager->persist($representative);
            $representatives[] = $representative;
        }
        $this->entityManager->flush();

        return $representatives;
    }

    /**
     * Get representative from api, save his, get district id.
     *
     * @param UserRepresentative $representative Representative object
     *
     * @return bool
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     */
    public function updateByRepresentativeInfo(UserRepresentative $representative): bool
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
                $representative->getRepresentative()
            );
        }

        return false;
    }

    /**
     * Synchronize $storageRepresentative with Cicero representative.
     *
     * @param \Civix\CoreBundle\Entity\UserRepresentative $representative
     *
     * @return bool
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     */
    public function synchronizeRepresentative(UserRepresentative $representative): bool
    {
        //find in current representative storage
        $ciceroRepresentative = $this->entityManager
            ->getRepository(Representative::class)
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
            $representative->setRepresentative($ciceroRepresentative);
        } else {
            $representative->setDistrict(null);
            $representative->setRepresentative();
        }

        $this->entityManager->persist($representative);
        $this->entityManager->flush();

        return (bool)$ciceroRepresentative;
    }

    public function synchronizeByStateCode($stateCode): void
    {
        $representatives = $this->entityManager->getRepository(UserRepresentative::class)
            ->findByState($stateCode);
        foreach ($representatives as $storageRepresentative) {
            $this->synchronizeRepresentative($storageRepresentative);
        }
    }

    protected function createRepresentative($response): Representative
    {
        $representative = new Representative();
        $this->populator->populate($representative, $response);

        $event = new RepresentativeEvent($representative);
        $this->dispatcher->dispatch(RepresentativeEvents::UPDATE, $event);

        return $representative;
    }

    /**
     * Save representative from api in representative storage.
     * Set link between representative and representative storage.
     *
     * @param array $apiCollection Object from Cicero API
     * @param Representative $representative Representative object
     *
     * @return bool
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     */
    protected function updateRepresentative($apiCollection, Representative $representative): bool
    {
        $collection = array_filter($apiCollection, function($repr) use ($representative) {
            return $representative->getId() === $repr->id;
        });
        if (!$collection) {
            $collection = array_filter($apiCollection, function ($repr) use ($representative) {
                $district = $representative->getDistrict();

                return $district && $district->getId() === $repr->office->district->id
                    && $representative->getFirstName() === $repr->first_name
                    && $representative->getLastName() === $repr->last_name;
            });
        }
        if ($collection) {
            $this->populator->populate($representative, reset($collection));

            $event = new AvatarEvent($representative);
            $this->dispatcher->dispatch(AvatarEvents::CHANGE, $event);

            $event = new RepresentativeEvent($representative);
            $this->dispatcher->dispatch(RepresentativeEvents::UPDATE, $event);

            $this->entityManager->persist($representative);
            $this->entityManager->flush();

            return true;
        }

        return false;
    }
}
