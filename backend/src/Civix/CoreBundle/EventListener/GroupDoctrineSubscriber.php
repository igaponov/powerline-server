<?php
namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\Group;
use Cocur\Slugify\Slugify;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;

class GroupDoctrineSubscriber implements EventSubscriber
{
    public function getSubscribedEvents()
    {
        return [
            Events::prePersist,
            Events::postPersist,
        ];
    }

    public function prePersist(LifecycleEventArgs $event)
    {
        $entity = $event->getEntity();

        if (!$entity instanceof Group || $entity->getSlug()) {
            return;
        }

        $slugify = new Slugify();
        $slug = $slugify->slugify($entity->getOfficialName());
        $conn = $event->getEntityManager()->getConnection();
        $stmt = $conn->prepare('SELECT slug FROM groups WHERE slug LIKE ? OR slug = ? ORDER BY LENGTH(slug) DESC, slug DESC');
        $stmt->execute([$slug.'-%', $slug]);
        while ($string = $stmt->fetchColumn()) {
            if ($string == $slug) {
                $number = 0;
            } else {
                $number = substr(strrchr($string, '-'), 1);
            }
            if (!is_numeric($number)) {
                continue;
            } else {
                $entity->setSlug($slug.'-'.(++$number));
                return;
            }
        }
        $entity->setSlug($slug);
    }

    /**
     * Workaround for the next doctrine error:
     * The given entity has no identity/no id values set.
     * It cannot be added to the identity map.
     *
     * https://github.com/doctrine/doctrine2/issues/4584
     *
     * @param LifecycleEventArgs $event
     */
    public function postPersist(LifecycleEventArgs $event)
    {
        $entity = $event->getEntity();

        if (!$entity instanceof Group) {
            return;
        }

        $attributes = new Group\AdvancedAttributes($entity);
        $em = $event->getEntityManager();
        $em->persist($attributes);
        $em->flush();
    }
}