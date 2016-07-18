<?php
namespace Civix\CoreBundle\Command;

use Civix\CoreBundle\Entity\User;
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
        $groupManager = $this->getContainer()->get('civix_core.group_manager');
        foreach ($users as $user) {
            /** @var User[] $user */
            $output->writeln('Processing user #'.$user[0]->getId());
            $groupManager->autoJoinUser($user[0]);
        }
    }
}