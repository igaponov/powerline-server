<?php
namespace Civix\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GeneratePasswordCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('civix:generate:password')
            ->addArgument('entity', InputArgument::REQUIRED, 'Entity class')
            ->addArgument('password', InputArgument::REQUIRED, 'Password to encode')
            ->addOption('salt', null, InputOption::VALUE_OPTIONAL, 'Salt');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $encoderFactory = $this->getContainer()->get('security.encoder_factory');
        $password = $encoderFactory
            ->getEncoder($input->getArgument('entity'))
            ->encodePassword(
                $input->getArgument('password'),
                $salt = $input->getOption('salt') ? : sha1(uniqid())
            );
        $output->writeln('Pass: '.$password.' / Salt: '.$salt);
    }
}