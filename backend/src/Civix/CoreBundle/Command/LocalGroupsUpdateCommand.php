<?php
namespace Civix\CoreBundle\Command;

use Civix\CoreBundle\Entity\User;
use Doctrine\ORM\Internal\Hydration\IterableResult;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LocalGroupsUpdateCommand extends ContainerAwareCommand
{
    protected function configure(): void
    {
        $this->setName('civix:local_groups:update')
            ->addArgument('users', InputArgument::IS_ARRAY|InputArgument::OPTIONAL, 'User ids');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        if ($input->getArgument('users')) {
            $users = array_map(function (User $user) {
                return [$user];
            }, (array)$this->getContainer()->get('doctrine.orm.entity_manager')
                ->getRepository(User::class)
                ->findBy(['id' => $input->getArgument('users')])
            );
        } else {
            /** @var IterableResult $users */
            $users = $this->getContainer()->get('doctrine.orm.entity_manager')
                ->getRepository(User::class)
                ->createQueryBuilder('u')
                ->getQuery()->iterate();
        }
        $manager = $this->getContainer()->get('civix_core.service.user_local_group_manager');
        foreach ($users as $user) {
            /** @var User[] $user */
            $output->writeln('Processing user #'.$user[0]->getId());
            $manager->joinLocalGroups($user[0]);
        }
    }
}