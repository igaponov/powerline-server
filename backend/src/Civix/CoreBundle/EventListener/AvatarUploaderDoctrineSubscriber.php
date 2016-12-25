<?php
namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Model\CropAvatarInterface;
use Civix\CoreBundle\Service\CropImage;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AvatarUploaderDoctrineSubscriber implements EventSubscriber
{
    const AVATAR_SIZE = 256;

    /**
     * @var CropImage
     */
    private $cropImage;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function getSubscribedEvents()
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];
    }

    public function __construct(CropImage $cropImage, LoggerInterface $logger)
    {
        $this->cropImage = $cropImage;
        $this->logger = $logger;
    }

    public function prePersist(LifecycleEventArgs $event)
    {
        $entity = $event->getEntity();

        if (!$entity instanceof CropAvatarInterface) {
            return;
        }

        if ($entity->getAvatar()) {
            $fileExt = pathinfo($entity->getAvatar()->getBasename(), PATHINFO_EXTENSION);
            $filename = uniqid().'.'.$fileExt;
            $tempFile = tempnam(sys_get_temp_dir(), 'avatar').'.'.$fileExt;

            $srcPath = $entity->getAvatar()->getRealPath();
            try {
                $this->cropImage->rebuildImage(
                    $tempFile,
                    $srcPath,
                    self::AVATAR_SIZE
                );
            } catch (\Exception $e) {
                $this->logger->error('Image '.$srcPath.'. '.$e->getMessage());
            }

            $entity->setAvatarFileName($tempFile);
            $fileUpload = new UploadedFile($tempFile, $filename);
            $entity->setAvatar($fileUpload);
        }
    }
}