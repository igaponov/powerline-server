<?php

namespace Civix\CoreBundle\Command;

use Civix\CoreBundle\Entity\ChangeableAvatarInterface;
use Civix\CoreBundle\Event\AvatarEvent;
use Civix\CoreBundle\Event\AvatarEvents;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AvatarUpdateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('civix:avatar:update')
            ->addArgument('entity', InputArgument::REQUIRED, 'Entity name');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityName = $input->getArgument('entity');
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $metadata = $em->getClassMetadata($entityName);
        $interface = ChangeableAvatarInterface::class;
        if (!in_array($interface, class_implements($metadata->getName()), true)) {
            throw new \RuntimeException('Invalid entity '.$entityName);
        }
        $dispatcher = $this->getContainer()->get('event_dispatcher');
        /** @var EntityRepository $repository */
        $repository = $em->getRepository($entityName);
        $iterator = $repository->createQueryBuilder('e')
            ->where('e.avatarFileName IS NULL')
            ->getQuery()->iterate();
        foreach ($iterator as $item) {
            /** @var ChangeableAvatarInterface $entity */
            $entity = $item[0];
            $event = new AvatarEvent($entity);
            $dispatcher->dispatch(AvatarEvents::CHANGE, $event);
            $em->flush($entity);
            $em->detach($entity);
        }
    }
}