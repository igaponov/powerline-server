<?php

namespace Civix\CoreBundle\EventListener;

use Civix\Component\ThumbnailGenerator\ThumbnailGeneratorInterface;
use Civix\CoreBundle\Event\PostEvent;
use Civix\CoreBundle\Event\PostEvents;
use Civix\CoreBundle\Event\UserPetitionEvent;
use Civix\CoreBundle\Event\UserPetitionEvents;
use Civix\CoreBundle\Model\TempFile;
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

    public static function getSubscribedEvents(): array
    {
        return [
            PostEvents::POST_CREATE => 'createPostFacebookThumbnail',
            UserPetitionEvents::PETITION_CREATE => 'createPetitionFacebookThumbnail',
        ];
    }

    public function __construct(ThumbnailGeneratorInterface $converter, UploadHandler $uploadHandler)
    {
        $this->converter = $converter;
        $this->uploadHandler = $uploadHandler;
    }

    public function createPostFacebookThumbnail(PostEvent $event): void
    {
        $post = $event->getPost();
        $image = $this->converter->generate($post);
        $image->encode('png', 100);
        $post->getFacebookThumbnail()->setFile(new TempFile($image->getEncoded()));
        $this->uploadHandler->upload($post, 'facebookThumbnail.file');
    }

    public function createPetitionFacebookThumbnail(UserPetitionEvent $event): void
    {
        $petition = $event->getPetition();
        $image = $this->converter->generate($petition);
        $image->encode('png', 100);
        $petition->getFacebookThumbnail()->setFile(new TempFile($image->getEncoded()));
        $this->uploadHandler->upload($petition, 'facebookThumbnail.file');
    }
}