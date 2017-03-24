<?php

namespace Civix\CoreBundle\Command;

use Civix\CoreBundle\Entity\User;
use Geocoder\Exception\NoResult;
use Geocoder\Model\AddressCollection;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateUserCoordinatesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('user:coordinates:update')
            ->addArgument('user', InputArgument::OPTIONAL, 'User ID to update');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $style = new SymfonyStyle($input, $output);
        $repository = $this->getContainer()->get('civix_core.repository.user_repository');
        $geocoder = $this->getContainer()->get('geocoder');
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $qb = $repository->createQueryBuilder('u');
        if ($input->getArgument('user')) {
            $qb->where('u.id = :id')
                ->setParameter('id', $input->getArgument('user'));
        }
        $countQb = clone $qb;
        $countQb->setParameters(clone $qb->getParameters());
        $count = $countQb->select('COUNT(u)')->getQuery()->getSingleScalarResult();
        if (!$count) {
            $style->error('No users found');
            return;
        }
        $iterator = $qb->getQuery()->iterate();
        $style->progressStart($count);
        foreach ($iterator as $k => $item) {
            /** @var User $user */
            $user = $item[0];
            try {
                $collection = $geocoder->geocode($user->getAddressQuery());
            } catch (NoResult $e) {
                $collection = new AddressCollection();
            }
            if ($collection->count()) {
                $coordinates = $collection->first()->getCoordinates();
                $user->setLatitude($coordinates->getLatitude());
                $user->setLongitude($coordinates->getLongitude());
                $em->persist($user);
            }
            if ($k % 20 === 0) {
                $em->flush();
                $em->clear();
            }
            $style->progressAdvance();
        }
        $em->flush();
        $style->progressFinish();
    }
}