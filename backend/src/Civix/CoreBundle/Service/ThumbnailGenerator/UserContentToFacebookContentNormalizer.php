<?php

namespace Civix\CoreBundle\Service\ThumbnailGenerator;

use Civix\Component\ThumbnailGenerator\ObjectNormalizerInterface;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Model\FacebookContent;
use Imgix\UrlBuilder;
use LogicException;
use Vich\UploaderBundle\Storage\StorageInterface;

class UserContentToFacebookContentNormalizer implements ObjectNormalizerInterface
{
    /**
     * @var StorageInterface
     */
    private $storage;
    /**
     * @var UrlBuilder
     */
    private $urlBuilder;

    public function __construct(StorageInterface $storage, UrlBuilder $urlBuilder)
    {
        $this->storage = $storage;
        $this->urlBuilder = $urlBuilder;
    }

    public function supports($object): bool
    {
        return $object instanceof Post || $object instanceof UserPetition;
    }

    /**
     * @param Post|UserPetition $object
     * @return FacebookContent|object
     * @throws \LogicException
     */
    public function normalize($object)
    {
        $user = $object->getUser();
        $group = $object->getGroup();
        if (!$user || !$group) {
            throw new LogicException('User and group should be provided for Facebook thumbnail.');
        }

        return new FacebookContent(
            $user->getFirstName()
                .' '.(!empty($user->getLastName()[0]) ? $user->getLastName()[0].'.' : ''),
            $user->getUsername(),
            $this->urlBuilder->createURL($this->storage->resolveUri($user, 'avatar')),
            $group->getOfficialName(),
            $this->urlBuilder->createURL($this->storage->resolveUri($group, 'avatar')),
            $object->getBody()
        );
    }
}