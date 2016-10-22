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
        $stmt = $conn->query('SELECT slug FROM groups WHERE slug LIKE ? OR slug = ? ORDER BY LENGTH(slug) DESC, slug DESC');
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
}