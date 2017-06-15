<?php

namespace Civix\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManager;
use Civix\CoreBundle\Entity\Activity;

class FixOwnerDataCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('fix:activity:owner-data')
            ->setDescription('Set activity owner data')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* @var $em EntityManager */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $iterator = $em->getRepository(Activity::class)
            ->createQueryBuilder('a')
            ->getQuery()->iterate();

        $count = 0;
        foreach ($iterator as $item) {
            /* @var $activity Activity */
            $activity = $item[0];
            if ($activity->getSuperuser()) {
                $activity->setSuperuser($activity->getSuperuser());
            }
            if ($activity->getGroup()) {
                $activity->setGroup($activity->getGroup());
            }
            if ($activity->getRepresentative()) {
                $activity->setRepresentative($activity->getRepresentative());
            }
            if ($activity->getUser()) {
                $activity->setUser($activity->getUser());
            }
            $em->flush($activity);
            $em->detach($activity);
            $count++;
        }
        $output->writeln(sprintf('<comment>%d activities updated</comment>', $count));
    }
}
