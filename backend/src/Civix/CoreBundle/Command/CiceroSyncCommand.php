<?php

namespace Civix\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Civix\CoreBundle\Entity\Representative;
use Doctrine\ORM\EntityManager;

class CiceroSyncCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('cicero:sync')
            ->setDescription('Synchronize data of representative storage from Cicero API (Check exists)')
            ->addOption(
                'records',
                null,
                InputOption::VALUE_REQUIRED,
                'How many records of representative should be synchronized with cicero api? (Default 1000)',
                1000
            )
            ->addOption(
                'state',
                null,
                InputOption::VALUE_OPTIONAL,
                'Update only representative in state'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* @var $entityManager EntityManager */
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        if ($input->getOption('state')) {
            $representatives = $entityManager->getRepository(Representative::class)
                ->findByState($input->getOption('state'));
        } else {
            $representatives = $entityManager->getRepository(Representative::class)
                ->findByUpdatedAt(new \DateTime(), $input->getOption('records'));
        }

        /** @var $representative \Civix\CoreBundle\Entity\Representative */
        foreach ($representatives as $representative) {
            $output->writeln(
                'Checking '.$representative->getUser()->getFirstName().' '.$representative->getUser()->getLastName()
            );

            $isUpdated = $this->getContainer()->get('civix_core.representative_manager')
                ->synchronizeRepresentative($representative);

            if (!$isUpdated) {
                $output->writeln(
                    '<error>'.$representative->getUser()->getFirstName().' '.
                    $representative->getUser()->getLastName().' is not found and will be removed</error>'
                );
            }
        }
        $output->writeln('Synchronization is completed');
    }
}
