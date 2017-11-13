<?php

namespace Civix\CoreBundle\Command;

use Civix\CoreBundle\Entity\Poll;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\SocialActivity;
use Civix\CoreBundle\Entity\UserFollow;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Service\SocialActivityFactory;
use Doctrine\ORM\EntityManagerInterface;
use SebastianBergmann\Diff\Differ;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FixTargetDataCommand extends Command
{
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var SocialActivityFactory
     */
    private $factory;

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Show all changes instead of updating')
            ->setDescription('Fix social activity target')
        ;
    }

    public function __construct(EntityManagerInterface $em, SocialActivityFactory $factory)
    {
        parent::__construct('fix:activity:target');
        $this->em = $em;
        $this->factory = $factory;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $iterator = $this->em->getRepository(SocialActivity::class)
            ->createQueryBuilder('a')
            ->getQuery()->iterate();

        $count = 0;
        foreach ($iterator as $item) {
            /* @var $activity SocialActivity */
            $activity = $item[0];
            switch ($activity->getType()) {
                case SocialActivity::TYPE_FOLLOW_REQUEST:
                    $target = $this->getFollowRequestTarget($activity);
                    break;
                case SocialActivity::TYPE_COMMENT_MENTIONED:
                    $target = $this->getCommentMentioned($activity);
                    break;
                default:
                    $target = $activity->getTarget();
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
                    $this->em->flush();
                }
            } elseif ($output->isVerbose()) {
                $output->writeln(sprintf('<comment>%d</comment> is not changed', $activity->getId()));
            }
            $this->em->detach($activity);
            $count++;
        }
        $output->writeln(sprintf('<comment>%d social activities updated</comment>', $count));
    }

    private function getFollowRequestTarget(SocialActivity $activity)
    {
        $userFollow = $this->em->getRepository(UserFollow::class)->findOneBy([
            'user' => $activity->getRecipient()->getId(),
            'follower' => $activity->getTarget()['id'] ?? 0,
        ]);
        if ($userFollow) {
            return $this->factory->getFollowRequestTarget($userFollow->getFollower());
        }

        return $activity->getTarget();
    }

    private function getCommentMentioned(SocialActivity $activity)
    {
        $target = $activity->getTarget();
        switch ($target['type']) {
            case 'user-petition':
                $qb = $this->em->getRepository(UserPetition\Comment::class)
                    ->createQueryBuilder('c')
                    ->where('c.petition = :id');
                break;
            case 'post':
                $qb = $this->em->getRepository(Post\Comment::class)
                    ->createQueryBuilder('c')
                    ->where('c.post = :id');
                break;
            default:
                $qb = $this->em->getRepository(Poll\Comment::class)
                    ->createQueryBuilder('c')
                    ->where('c.question = :id');
                break;
        }
        $qb->setParameter(':id', $target['id']);
        if (!empty($target['preview'])) {
            $qb->andWhere('c.commentBody LIKE :preview')
                ->setParameter(':preview', mb_substr($target['preview'], 0, 20, 'utf8').'%');
        }
        $comment = $qb->getQuery()->setMaxResults(1)->getOneOrNullResult();
        if ($comment) {
            $target = $this->factory->getCommentMentionedTarget($comment);
        }

        return $target;
    }
}
