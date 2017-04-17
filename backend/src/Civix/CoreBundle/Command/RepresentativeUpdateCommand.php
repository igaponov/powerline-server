<?php

namespace Civix\CoreBundle\Command;

use Civix\CoreBundle\Event\UserEvent;
use Civix\CoreBundle\Event\UserEvents;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Entity\User;

class RepresentativeUpdateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('cicero:update')
            ->setDescription('Update data of representative from Cicero API.')
            ->addOption(
                'purge',
                null,
                InputOption::VALUE_NONE,
                'If set, all saved storage representative will be rejected, incorrect districts'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ciceroService = $this->getContainer()->get('civix_core.cicero_api');
        $userManager = $this->getContainer()->get('civix_core.user_manager');
        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $dispatcher = $this->getContainer()->get('event_dispatcher');

        // update representative by user profile
        if ($input->getOption('by-users')) {
            $output->writeln('Start update by user\'s profiles');

            $output->writeln(' Get users with address');
            $users = $entityManager->getRepository(User::class)
                    ->getAllUsersWithAddressProfile();

            foreach ($users as $user) {
                $output->writeln(' Get all districts id (fill representative storage) for user '.
                    $user->getFirstName().' '.$user->getLastName());
                $userManager->updateDistrictsIds($user);
                $output->writeln(' Join to global groups for user '.$user->getFirstName().' '.$user->getLastName());

                $entityManager->persist($user);
                $entityManager->flush($user);

                $event = new UserEvent($user);
                $dispatcher->dispatch(UserEvents::ADDRESS_CHANGE, $event);
            }
        }

        $output->writeln('Update link between active representative and storage');
        //update link between active representative and storage
        /** @var Representative[] $representatives */
        $representatives = $entityManager->getRepository(Representative::class)
                ->getQueryRepresentativeByStatus(Representative::STATUS_ACTIVE)->getResult();

        foreach ($representatives as $representative) {
            $ciceroService->updateByRepresentativeInfo($representative);
        }

        $output->writeln('Update complete.');
    }
}
