<?php
namespace Civix\CoreBundle\Command;

use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Event\UserEvent;
use Civix\CoreBundle\Event\UserEvents;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FixLocalUserGroupsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('fix-local-user-groups');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $users = $this->getContainer()->get('doctrine')
            ->getRepository(User::class)
            ->createQueryBuilder('u')
            ->getQuery()->iterate();
        $dispatcher = $this->getContainer()->get('event_dispatcher');
        foreach ($users as $user) {
            /** @var User[] $user */
            $output->writeln('Processing user #'.$user[0]->getId());
            $event = new UserEvent($user[0]);
            $dispatcher->dispatch(UserEvents::ADDRESS_CHANGE, $event);
        }
    }
}