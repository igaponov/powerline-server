<?php

namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Event\AvatarEvent;
use Civix\CoreBundle\Event\AvatarEvents;
use Civix\CoreBundle\Model\Avatar\DefaultAvatar;
use Civix\CoreBundle\Model\Avatar\DefaultAvatarInterface;
use Civix\CoreBundle\Model\Avatar\FirstLetterDefaultAvatar;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use YoHang88\LetterAvatar\LetterAvatar;

class UserAvatarSubscriber implements EventSubscriberInterface
{
    const AVATAR_WIDTH = 256;
    const AVATAR_HEIGHT = 256;

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
        $file = $entity->getAvatarFile();

        try {
            // new avatar
            if ($file) {
                $image = $this->generateAvatar($file);
            // default avatar if an entity has no one
            } elseif (!$entity->getAvatarFileName()) {
                $image = $this->generateDefaultAvatar($entity->getDefaultAvatar());
            } else {
                return;
            }
            $tempFile = tempnam('php://temp', 'avatar_');
            $image->save($tempFile, 100);
            $uploadedFile = new UploadedFile($tempFile, uniqid('avatar_', true));
            $entity->setAvatar($uploadedFile);
            // update a field to dispatch doctrine's "update" event
            // @todo handle image in a custom event listener
            $entity->setAvatarFileName($uploadedFile->getFilename());
        } catch (\Exception $e) {
            $this->logger->critical('Avatar uploading error.', ['exception' => $e]);
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