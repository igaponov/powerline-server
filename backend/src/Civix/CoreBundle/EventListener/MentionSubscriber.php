<?php
namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\HtmlBodyInterface;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserMentionableInterface;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;

class MentionSubscriber implements EventSubscriber
{
    public function getSubscribedEvents()
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];
    }

    public function prePersist(LifecycleEventArgs $event)
    {
        $this->parseBody($event, true);
    }

    public function preUpdate(LifecycleEventArgs $event)
    {
        $this->parseBody($event);
    }

    public function parseBody(LifecycleEventArgs $event, $notify = false)
    {
        $entity = $event->getEntity();
        $manager = $event->getEntityManager();

        if (!$entity instanceof HtmlBodyInterface) {
            return;
        }

        $content = strtr($entity->getBody(), ['<' => '&lt;', '>' => '&gt;']);
        $content = preg_replace_callback(
            '/@([a-zA-Z0-9._-]+[a-zA-Z0-9])/',
            function ($matches) use ($manager, $entity, $notify) {
                $username = $matches[1];
                if ($username === 'everyone'
                    && $entity instanceof BaseComment
                    && method_exists($entity->getCommentedEntity(), 'getGroup')
                ) {
                    /** @var Group $group */
                    $group = $entity->getCommentedEntity()
                        ->getGroup();
                    if (!$group->isOwner($entity->getUser()) && !$group->isManager($entity->getUser())) {
                        return '@'.$username;
                    }
                    $userIds = $manager->getRepository(User::class)
                        ->findAllMemberIdsByGroup($group);
                    foreach ($userIds as $userId) {
                        /** @var User $user */
                        $user = $manager->getReference(User::class, $userId);
                        if ($notify && $entity instanceof UserMentionableInterface) {
                            $entity->addMentionedUser($user);
                        }
                    }

                    return '@'.$username;
                } else {
                    $user = $manager->getRepository(User::class)
                        ->findOneBy(['username' => $username]);

                    if (!$user) {
                        return '@'.$username;
                    }
                    if ($notify && $entity instanceof UserMentionableInterface) {
                        $entity->addMentionedUser($user);
                    }

                    return "<a data-user-id=\"{$user->getId()}\">@$username</a>";
                }
            },
            $content
        );
        $entity->setHtmlBody($content);
        if ($event instanceof PreUpdateEventArgs) {
            $em = $event->getEntityManager();
            $uow = $em->getUnitOfWork();
            $meta = $em->getClassMetadata(get_class($entity));
            $uow->recomputeSingleEntityChangeSet($meta, $entity);
        }
    }
}