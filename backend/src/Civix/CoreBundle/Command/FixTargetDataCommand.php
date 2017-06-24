<?php

namespace Civix\CoreBundle\Command;

use Civix\CoreBundle\Entity\SocialActivity;
use Civix\CoreBundle\Entity\UserFollow;
use Civix\CoreBundle\Service\SocialActivityFactory;
use SebastianBergmann\Diff\Differ;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManager;

class FixTargetDataCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('fix:activity:target')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Show all changes instead of updating')
            ->setDescription('Fix social activity target')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* @var $em EntityManager */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $iterator = $em->getRepository(SocialActivity::class)
            ->createQueryBuilder('a')
            ->getQuery()->iterate();

        $count = 0;
        foreach ($iterator as $item) {
            /* @var $activity SocialActivity */
            $activity = $item[0];
            $target = $activity->getTarget();
            switch ($activity->getType()) {
                case SocialActivity::TYPE_FOLLOW_REQUEST:
                    $userFollow = $em->getRepository(UserFollow::class)->findOneBy([
                        'user' => $activity->getRecipient()->getId(),
                        'follower' => $target['id'] ?? 0,
                    ]);
                    if ($userFollow) {
                        $activity->setTarget(SocialActivityFactory::getFollowRequestTarget($userFollow));
                    }
                    break;
            }
            if ($target !== $activity->getTarget()) {
                if ($input->getOption('dry-run')) {
                    $differ = new Differ();
                    $diff = $differ->diff(var_export($target, 1), var_export($activity->getTarget(), 1));
                    $output->write(sprintf('<comment>%d</comment>', $activity->getId()));
                    $output->writeln(preg_replace([
                        "/^(.*\n){3}/",
                        '/\+.+/',
                        '/\-.+/',
                    ], [
                        '',
                        '<info>$0</info>',
                        '<error>$0</error>',
                    ], $diff));
                } else {
                    $em->flush($activity);
                }
            } elseif ($output->isVerbose()) {
                $output->writeln(sprintf('<comment>%d</comment> is not changed', $activity->getId()));
            }
            $em->detach($activity);
            $count++;
        }
        $output->writeln(sprintf('<comment>%d social activities updated</comment>', $count));
    }
}
