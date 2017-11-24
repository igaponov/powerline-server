<?php

namespace Civix\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Civix\CoreBundle\Entity\Superuser;
use Symfony\Component\Console\Style\SymfonyStyle;

class SuperuserPasswordCommand extends ContainerAwareCommand
{
    protected function configure(): void
    {
        $this
            ->setName('civix:superuser:password')
            ->setDescription('Update password for superuser according to username')
            ->setHelp('Usage: <info>php bin/console civix:superuser:password admin newpassword</info>')
            ->addArgument(
                'username',
                InputArgument::OPTIONAL, 'Superuser\'s username'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $style = new SymfonyStyle($input, $output);
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        if (!$username = $input->getArgument('username')) {
            $username = $style->ask('Username:', 'admin');
        }
        $superuser = $entityManager->getRepository(Superuser::class)
            ->findOneBy(['username' => $username]);
        if ($superuser) {
            $newPassword = $style->askHidden('New password:');
            $encoder = $this->getContainer()->get('security.encoder_factory')->getEncoder($superuser);
            $password = $encoder->encodePassword($newPassword, $superuser->getSalt());
            $superuser->setPassword($password);
            $entityManager->persist($superuser);
            $entityManager->flush();
            $style->success('Password of superuser '.$username.' has been updated.');
        } else {
            $style->error('Superuser with username '.$username.' has not been found.');
        }
    }
}
