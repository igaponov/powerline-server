<?php
namespace Civix\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MailgunCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('mailgun')
            ->addArgument('method', InputArgument::REQUIRED, 'Method: get, create, remove, madd, mremove, exist')
            ->addOption('name', 'a', InputOption::VALUE_OPTIONAL, 'List name')
            ->addOption('description', 'd', InputOption::VALUE_OPTIONAL, 'List description', '')
            ->addOption('member', 'm', InputOption::VALUE_IS_ARRAY|InputOption::VALUE_OPTIONAL, 'List members', []);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $api = $this->getContainer()->get('civix_core.mailgun');
        $listName = $input->getOption('name');
        $members = $input->getOption('member');
        $method = $input->getArgument('method');
        $response = null;
        switch ($method) {
            case 'get':
                $response = $api->listGetAction();
                break;
            case 'create':
                $response = $api->listCreateAction($listName, $input->getOption('description'));
                break;
            case 'remove':
                $response = $api->listRemoveAction($listName);
                break;
            case 'madd':
                if (count($members) === 1) {
                    $response = $api->listAddMemberAction($listName, reset($members), 'Test name');
                } else {
                    $response = $api->listAddMembersAction($listName, $members);
                }
                break;
            case 'mremove':
                foreach ($members as $member) {
                    $response = $api->listRemoveMemberAction($listName, $member);
                }
                break;
            case 'exist':
                $response = ['exist' => $api->listExistsAction($listName)];
                break;
            default:
                throw new \RuntimeException("Wrong method name $method");
        }

        $output->writeln(var_export($response, true));
    }
}