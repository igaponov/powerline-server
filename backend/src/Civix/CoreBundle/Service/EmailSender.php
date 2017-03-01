<?php

namespace Civix\CoreBundle\Service;

use Civix\CoreBundle\Entity\Representative;
use Symfony\Component\Templating\EngineInterface;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\User;

class EmailSender
{
    private $mailer;
    private $templating;
    private $mailFrom;
    private $domain;

    public function __construct(
        \Swift_Mailer $mailer,
        EngineInterface $templating,
        $mailFrom,
        $domain
    ) {
        $this->mailer = $mailer;
        $this->templating = $templating;
        $this->mailFrom = $mailFrom;
        $this->domain = $domain;
    }

    public function sendResetPasswordEmail($emailTo, $templateParams)
    {
        $message = $this->createMessage(
            'Reset password',
            $emailTo,
            'CivixFrontBundle:Email:reset_password.html.twig',
            $templateParams
        );
        $this->mailer->send($message);
    }

    public function sendInviteFromGroup($emailTo, Group $group)
    {
        $message = $this->createMessage(
            'You’ve been invited to a group on Powerline',
            $emailTo,
            'CivixFrontBundle:Email:invite.html.twig',
            array(
                'group' => $group,
                'link' => '#',
            )
        );
        $this->mailer->send($message);
    }

    public function sendRegistrationSuccessGroup(Group $group)
    {
        $message = $this->createMessage(
            'Group successful registered',
            $group->getManagerEmail(),
            'CivixFrontBundle:Email:group_registered.html.twig',
            array(
                'name' => $group->getOfficialName(),
            )
        );
        $this->mailer->send($message);
    }

    public function sendNewRepresentativeNotification($emailTo, $representativeTitle)
    {
        $message = $this->createMessage(
            'New Representative Registration',
            $emailTo,
            'CivixFrontBundle:Email:notification.html.twig',
            array('title' => $representativeTitle)
        );
        $this->mailer->send($message);
    }

    public function sendToApprovedRepresentative(Representative $representative)
    {
        $message = $this->createMessage(
            'Representative Registration approved',
            $representative->getEmail(),
            'CivixFrontBundle:Email:representative_approved.html.twig',
            array(
                    'name' => $representative->getUser()->getFirstName().' '.$representative->getUser()->getLastName(),
            )
        );
        $this->mailer->send($message);
    }

    public function sendRegistrationEmail(User $user)
    {
        $message = $this->createMessage(
            'Welcome to Powerline',
            $user->getEmail(),
            'CivixCoreBundle:Email:registration.html.twig',
            compact('user'),
            'welcome@powerli.ne'
        );
        $this->mailer->send($message);
    }

    /**
     * @param $subject
     * @param $emailTo
     * @param $templatePath
     * @param $templateParams
     * @param null $mailFrom
     * @return \Swift_Message
     */
    private function createMessage($subject, $emailTo, $templatePath, $templateParams, $mailFrom = null)
    {
        $body = $this->templating->render($templatePath, $templateParams);
        $message = \Swift_Message::newInstance($subject, $body, 'text/html');
        $message->setFrom($mailFrom ?: $this->mailFrom)->setTo($emailTo);

        return $message;
    }
}
