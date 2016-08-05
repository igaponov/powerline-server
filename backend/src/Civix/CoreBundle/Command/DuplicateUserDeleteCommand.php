<?php
namespace Civix\CoreBundle\Command;

use Civix\CoreBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DuplicateUserDeleteCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('duplicate:user:delete')
            ->setDescription('Command to choose and delete duplicate users');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repository = $em->getRepository(User::class);
        $users = $repository->findWithDuplicateEmails();
        if (!$users) {
            $output->writeln('No duplicate users');
        }
        $select = array_map(function (User $user) {
            return sprintf('%s, %s (%s)',
                $user->getFullName(),
                $user->getEmail(),
                $user->getUsername()
            );
        }, $users);
        /** @var DialogHelper $dialog */
        $dialog = $this->getHelper('dialog');
        while (count($select) > 1) {
            $key = $dialog->select(
                $output,
                '<question>Select user id to delete: </question>',
                $select
            );
            $em->remove($users[$key]);
            $em->flush();
            unset($users[$key]);
            unset($select[$key]);
        }
    }
}