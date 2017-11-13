<?php

namespace Civix\CoreBundle\Command;

use Civix\CoreBundle\Entity\UserRepresentative;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OpenstatesUpdateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('openstates:update')
            ->setDescription('Update data in representative storage (add id OpenState API)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $openstatesService = $this->getContainer()->get('civix_core.openstates_api');
        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        $output->writeln('Get all storage representative without link openstates api');
        $representatives = $entityManager->getRepository(UserRepresentative::class)
            ->getByEmptyOpenStatesId();

        foreach ($representatives as $representative) {
            $openstatesService->updateRepresentativeProfile($representative);
            $entityManager->persist($representative);

            $output->writeln('Update representative '.$representative->getFirstName().
                ' '.$representative->getLastName().', set openstateID = '.$representative->getOpenstateId());
        }

        $entityManager->flush();

        $output->writeln('Update complete');
    }
}
