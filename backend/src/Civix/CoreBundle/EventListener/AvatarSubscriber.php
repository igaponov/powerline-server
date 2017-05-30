<?php

namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Event\AvatarEvent;
use Civix\CoreBundle\Event\AvatarEvents;
use Civix\CoreBundle\Model\Avatar\DefaultAvatar;
use Civix\CoreBundle\Model\Avatar\DefaultAvatarInterface;
use Civix\CoreBundle\Model\Avatar\FirstLetterDefaultAvatar;
use Civix\CoreBundle\Model\TempFile;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use YoHang88\LetterAvatar\LetterAvatar;


class AvatarSubscriber implements EventSubscriberInterface
{
    const AVATAR_WIDTH = 250;
    const AVATAR_HEIGHT = 250;

    /**
     * @var ImageManager
     */
    private $manager;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public static function getSubscribedEvents()
    {
        return [
            AvatarEvents::CHANGE => 'handleAvatar',
        ];
    }

    public function __construct(ImageManager $manager, LoggerInterface $logger)
    {
        $this->manager = $manager;
        $this->logger = $logger;
    }

    public function handleAvatar(AvatarEvent $event)
    {
        $entity = $event->getEntity();
        $file = $entity->getAvatar();

        try {
            // new avatar
            if ($file instanceof UploadedFile) {
                $image = $this->generateAvatar($file);
                $image->save($file->getPathname(), 100);
            // default avatar if an entity has no one
            } elseif (!$entity->getAvatarFileName()) {
                $image = $this->generateDefaultAvatar($entity->getDefaultAvatar());
                $file = new TempFile($image->encode(null, 100));
            } else {
                return;
            }
            $entity->setAvatar($file);
            // update a field to dispatch doctrine's "update" event
            // @todo handle image uploading in a custom event listener
            $entity->setAvatarFileName($file->getFilename());
        } catch (\Exception $e) {
            $this->logger->critical('Avatar resizing error.', ['exception' => $e]);
        }
    }

    /**
     * @param $fileName
     * @return Image
     */
    private function generateAvatar($fileName)
    {
        $image = $this->manager->make($fileName);

        return $image->resize(self::AVATAR_WIDTH, self::AVATAR_HEIGHT);
    }

    /**
     * @param DefaultAvatarInterface $defaultAvatar
     * @return Image
     */
    private function generateDefaultAvatar(DefaultAvatarInterface $defaultAvatar)
    {
        if ($defaultAvatar instanceof DefaultAvatar) {
            $image = $this->generateAvatar($defaultAvatar->getPath());
        } elseif ($defaultAvatar instanceof FirstLetterDefaultAvatar) {
            $avatar = new LetterAvatar($defaultAvatar->getLetter(), 'square', self::AVATAR_WIDTH);
            $image = $avatar->generate();
        } else {
            throw new \RuntimeException('Class '.get_class($defaultAvatar).' is not supported');
        }

        return $image;
    }
}