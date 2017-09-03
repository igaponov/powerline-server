<?php
namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\HtmlBodyInterface;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Event\CommentEvent;
use Civix\CoreBundle\Event\CommentEvents;
use Civix\CoreBundle\Event\PostEvent;
use Civix\CoreBundle\Event\PostEvents;
use Civix\CoreBundle\Event\UserPetitionEvent;
use Civix\CoreBundle\Event\UserPetitionEvents;
use Civix\CoreBundle\Repository\UserRepository;
use Civix\CoreBundle\Service\SocialActivityManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MentionSubscriber implements EventSubscriberInterface
{
    const PATTERN = '/@([a-zA-Z0-9._-]+[a-zA-Z0-9])/';

    /**
     * @var UserRepository
     */
    private $repository;
    /**
     * @var SocialActivityManager
     */
    private $activityManager;

    public static function getSubscribedEvents(): array
    {
        return [
            PostEvents::POST_PRE_CREATE => 'onPostPreCreate',
            UserPetitionEvents::PETITION_PRE_CREATE => 'onPetitionPreCreate',
            CommentEvents::PRE_CREATE => 'onCommentPreCreate',
            CommentEvents::CREATE => ['onCommentCreate', -100],
            PostEvents::POST_CREATE => ['onPostCreate', -100],
        ];
    }

    public function __construct(UserRepository $repository, SocialActivityManager $activityManager)
    {
        $this->repository = $repository;
        $this->activityManager = $activityManager;
    }

    public function onPostPreCreate(PostEvent $event)
    {
        $post = $event->getPost();
        $this->handleHtmlBody($post);
    }

    public function onPetitionPreCreate(UserPetitionEvent $event)
    {
        $petition = $event->getPetition();
        $this->handleHtmlBody($petition);
    }

    public function onCommentPreCreate(CommentEvent $event)
    {
        $comment = $event->getComment();
        $this->handleHtmlBody($comment);
    }

    public function onCommentCreate(CommentEvent $event)
    {
        $comment = $event->getComment();
        $users = $this->fetchMentionedUsers($comment->getCommentBody());
        $users = array_merge($users, $this->fetchEveryone($comment, ...$users));
        $this->activityManager->noticeCommentMentioned($comment, ...$users);
    }

    public function onPostCreate(PostEvent $event)
    {
        $post = $event->getPost();
        $users = $this->fetchMentionedUsers($post->getBody());
        $this->activityManager->noticePostMentioned($post, ...$users);
    }

    private function handleHtmlBody(HtmlBodyInterface $entity)
    {
        $pairs = ['<' => '&lt;', '>' => '&gt;'];
        if (preg_match_all('/@([a-zA-Z0-9._-]+[a-zA-Z0-9])/', $entity->getBody(), $matches)) {
            $usernames = array_unique($matches[1]);
            $keys = array_keys($usernames, 'everyone', true);
            foreach ($keys as $key) {
                unset($usernames[$key]);
            }
            /** @var User[] $users */
            $users = $this->repository->findBy(['username' => $usernames]);
            foreach ($users as $user) {
                $username = $user->getUsername();
                $pairs['@'.$username] = "<a data-user-id=\"{$user->getId()}\">@{$username}</a>";
            }
        }
        $entity->setHtmlBody(strtr($entity->getBody(), $pairs));
    }

    private function fetchMentionedUsers(string $text)
    {
        if (preg_match_all(self::PATTERN, $text, $matches)) {
            $usernames = array_unique($matches[1]);
            $keys = array_keys($usernames, 'everyone', true);
            foreach ($keys as $key) {
                unset($usernames[$key]);
            }
            return $this->repository->findBy(['username' => $usernames]);
        }

        return [];
    }

    private function fetchEveryone(BaseComment $comment, User ...$exclude)
    {
        $users = [];
        if (preg_match('/@everyone\W?/', $comment->getCommentBody(), $matches)) {
            $entity = $comment->getCommentedEntity();
            if (method_exists($entity, 'getGroup')) {
                /** @var Group $group */
                $group = $entity->getGroup();
                if ($group->isOwner($comment->getUser()) || $group->isManager($comment->getUser())) {
                    $users = $this->repository->findAllMembersByGroup($group, ...$exclude);
                }
            }
        }

        return $users;
    }
}