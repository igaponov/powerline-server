<?php

namespace Civix\CoreBundle\EventListener;

use Civix\Component\ThumbnailGenerator\ThumbnailGeneratorInterface;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Event\PostEvent;
use Civix\CoreBundle\Event\PostEvents;
use Civix\CoreBundle\Event\UserPetitionEvent;
use Civix\CoreBundle\Event\UserPetitionEvents;
use Civix\CoreBundle\Model\TempFile;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Vich\UploaderBundle\Handler\UploadHandler;

class ThumbnailSubscriber implements EventSubscriberInterface
{
    /**
     * @var ThumbnailGeneratorInterface
     */
    private $converter;
    /**
     * @var UploadHandler
     */
    private $uploadHandler;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public static function getSubscribedEvents(): array
    {
        return [
            PostEvents::POST_CREATE => 'createPostFacebookThumbnail',
            UserPetitionEvents::PETITION_CREATE => 'createPetitionFacebookThumbnail',
        ];
    }

    public function __construct(
        ThumbnailGeneratorInterface $converter,
        UploadHandler $uploadHandler,
        LoggerInterface $logger
    ) {
        $this->converter = $converter;
        $this->uploadHandler = $uploadHandler;
        $this->logger = $logger;
    }

    public function createPostFacebookThumbnail(PostEvent $event): void
    {
        $post = $event->getPost();
        try {
            $this->createFacebookThumbnail($post);
        } catch (\Exception $e) {
            $this->logger->critical('Post thumbnail generation error: '.$e->getMessage(), [
                'id' => $post->getId(),
                'e' => $e,
            ]);
        }
    }

    public function createPetitionFacebookThumbnail(UserPetitionEvent $event): void
    {
        $petition = $event->getPetition();
        try {
            $this->createFacebookThumbnail($petition);
        } catch (\Exception $e) {
            $this->logger->critical('Petition thumbnail generation error: '.$e->getMessage(), [
                'id' => $petition->getId(),
                'e' => $e,
            ]);
        }
    }

    /**
     * @param Post|UserPetition $entity
     */
    private function createFacebookThumbnail($entity)
    {
        $image = $this->converter->generate($entity);
        $image->encode('png', 100);
        $entity->getFacebookThumbnail()
            ->setFile(new TempFile($image->getEncoded()));
        $this->uploadHandler->upload($entity, 'facebookThumbnail.file');
    }
}