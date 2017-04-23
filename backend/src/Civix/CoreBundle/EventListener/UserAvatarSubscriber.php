<?php

namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Event\UserEvent;
use Civix\CoreBundle\Event\UserEvents;
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
            UserEvents::PRE_REGISTRATION => 'generateAvatar',
            UserEvents::PROFILE_UPDATE => 'generateAvatar',
            UserEvents::AVATAR_CHANGE => 'generateAvatar',
        ];
    }

    public function __construct(ImageManager $manager, LoggerInterface $logger)
    {
        $this->manager = $manager;
        $this->logger = $logger;
    }

    public function generateAvatar(UserEvent $event)
    {
        $user = $event->getUser();
        $fileName = $user->getAvatarFile();

        try {
            // new avatar
            if ($fileName) {
                $image = $this->generateUserAvatar($fileName);
            // default avatar if a user has no one
            } elseif (!$user->getAvatarFileName()) {
                $image = $this->generateDefaultUserAvatar(substr($user->getFirstName(), 0, 1));
            } else {
                return;
            }
            $tempFile = tempnam(sys_get_temp_dir(), 'avatar_');
            $image->save($tempFile, 100);
            $image->save(__DIR__.'/img.png');
            $file = new UploadedFile($tempFile, uniqid('avatar_', true));
            $user->setAvatar($file);
        } catch (\Exception $e) {
            $this->logger->critical('Avatar uploading error.', ['exception' => $e]);
        }
    }

    /**
     * @param $fileName
     * @return Image
     */
    private function generateUserAvatar($fileName)
    {
        $image = $this->manager->make($fileName);

        return $image->resize(self::AVATAR_WIDTH, self::AVATAR_HEIGHT);
    }

    /**
     * @param $userName
     * @return Image
     */
    private function generateDefaultUserAvatar($userName)
    {
        $avatar = new LetterAvatar($userName, 'square', self::AVATAR_WIDTH);

        return $avatar->generate();
    }
}