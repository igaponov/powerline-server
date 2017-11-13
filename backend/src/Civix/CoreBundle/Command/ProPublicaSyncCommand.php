<?php

namespace Civix\CoreBundle\Command;

use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Repository\RepresentativeRepository;
use Civix\CoreBundle\Service\ProPublicaRepresentativePopulator;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Command\ServiceClientInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProPublicaSyncCommand extends Command
{
    /**
     * @var ServiceClientInterface
     */
    private $client;
    /**
     * @var ProPublicaRepresentativePopulator
     */
    private $populator;
    /**
     * @var RepresentativeRepository
     */
    private $repository;
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(
        ServiceClientInterface $client,
        ProPublicaRepresentativePopulator $populator,
        RepresentativeRepository $repository,
        EntityManagerInterface $em
    ) {
        parent::__construct('civix:propublica:sync');
        $this->client = $client;
        $this->populator = $populator;
        $this->repository = $repository;
        $this->em = $em;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $results = $this->client->getNewMembers();
        if (!empty($results['results'][0]['members'])) {
            /** @var array $members */
            $members = $results['results'][0]['members'];
            foreach ($members as $member) {
                $id = $member['id'];
                /** @var Representative $representative */
                $representative = $this->repository->findOneBy(['bioguide' => $id]);
                if (!$representative) {
                    continue;
                }
                /** @noinspection PhpUndefinedMethodInspection */
                $result = $this->client->getMember(['id' => $id]);
                $this->populator->populate($representative, $result['results'][0]);
            }
            $this->em->flush();
        }
    }
}