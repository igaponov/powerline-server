<?php

namespace Civix\CoreBundle\Command;

use Civix\Component\Notification\DataFactory\OneSignalDataFactory;
use Civix\Component\Notification\Model\Device;
use Civix\Component\Notification\PushMessage;
use Civix\CoreBundle\Entity\SocialActivity;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Service\PushSender;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OneSignalButtonsCommand extends Command
{
    /**
     * @var OneSignalDataFactory
     */
    private $factory;

    public function __construct(OneSignalDataFactory $factory)
    {
        parent::__construct('civix:one_signal:buttons');
        $this->factory = $factory;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $types = [
            PushSender::TYPE_PUSH_USER_PETITION_BOOSTED => 'User petition boosted',
            PushSender::TYPE_PUSH_OWN_USER_PETITION_BOOSTED => 'Own user petition boosted',
            SocialActivity::TYPE_COMMENT_MENTIONED => 'Comment mentioned',
            SocialActivity::TYPE_POST_MENTIONED => 'Post mentioned',
            SocialActivity::TYPE_FOLLOW_REQUEST => 'Follow request',
            SocialActivity::TYPE_FOLLOW_POST_CREATED => 'Follow post created',
            PushSender::TYPE_PUSH_POST_BOOSTED => 'Post boosted',
            SocialActivity::TYPE_FOLLOW_USER_PETITION_CREATED => 'Follow user petition created',
            PushSender::TYPE_PUSH_OWN_POST_BOOSTED => 'Own post boosted',
            PushSender::TYPE_PUSH_INVITE => 'Invite',
            SocialActivity::TYPE_GROUP_PERMISSIONS_CHANGED => 'Group permissions changed',
            SocialActivity::TYPE_OWN_POLL_COMMENTED => 'Own poll commented',
            SocialActivity::TYPE_OWN_POLL_ANSWERED => 'Poll answered',
            SocialActivity::TYPE_FOLLOW_POLL_COMMENTED => 'Follow poll commented',
            SocialActivity::TYPE_OWN_POST_COMMENTED => 'Own post commented',
            SocialActivity::TYPE_OWN_POST_VOTED => 'Own post voted',
            SocialActivity::TYPE_FOLLOW_POST_COMMENTED => 'Follow post commented',
            SocialActivity::TYPE_OWN_USER_PETITION_COMMENTED => 'Own user petition commented',
            SocialActivity::TYPE_OWN_USER_PETITION_SIGNED => 'Own user petition signed',
            SocialActivity::TYPE_FOLLOW_USER_PETITION_COMMENTED => 'Follow user petition commented',
            SocialActivity::TYPE_COMMENT_REPLIED => 'Comment replied',
            PushSender::TYPE_PUSH_ANNOUNCEMENT => 'Announcement',
            PushSender::TYPE_PUSH_POST_SHARED => 'Post shared',
            'group_petition' => 'Petition created',
            'group_question' => 'Poll created',
            'group_news' => 'News created',
            'group_event' => 'Event created',
            'group_payment_request' => 'Payment request created',
            'group_payment_request_crowdfunding' => 'Crowdfunding request created',
        ];

        $recipient = new User();
        $t = '    ';
        foreach ($types as $type => $title) {
            $message = new PushMessage($recipient, '-', '-', $type);
            $data = $this->factory->createData($message, new Device($recipient));
            /** @var array $buttons */
            $buttons = $data['buttons'];
            $output->writeln("<tr style=\"background-color: lightgrey\"><td colspan=\"3\">$title</td></tr>");
            foreach ($buttons as $button) {
                $output->writeln(sprintf(
                    "<tr style=\"border: 1px solid lightgrey\">\n$t<td>%s</td>\n$t<td>%s</td>\n$t<td>%s</td>\n</tr>",
                    $button['id'],
                    $button['text'],
                    $button['icon']
                ));
            }
        }
    }
}