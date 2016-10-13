<?php

namespace Civix\CoreBundle\Repository;

use Civix\CoreBundle\Entity\HashTaggableInterface;
use Civix\CoreBundle\Entity\HtmlBodyInterface;
use Doctrine\ORM\EntityRepository;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\Poll\Question\Petition as PollPetition;
use Civix\CoreBundle\Entity\HashTag;
use Civix\CoreBundle\Parser\Tags as HashTagParser;

/**
 * HashTagRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class HashTagRepository extends EntityRepository
{
    /**
     * @param HashTaggableInterface $entity
     * @param bool     $saveTagsInEntity
     */
    public function addForTaggableEntity(HashTaggableInterface $entity, $saveTagsInEntity = true)
    {
        if ($entity instanceof HtmlBodyInterface) {
            $content = $entity->getBody();
        } elseif ($entity instanceof PollPetition) {
            $content = $entity->getPetitionBody();
        } elseif ($entity instanceof Question) {
            $content = $entity->getSubject();
        } else {
            return;
        }
        $em = $this->getEntityManager();
        $tags = HashTagParser::parseHashTags($content);
        foreach ($entity->getHashTags() as $tag) {
            if (!in_array($tag->getName(), $tags['parsed'])) {
                $entity->getHashTags()->removeElement($tag);
            }
        }
        foreach ($tags['parsed'] as $name) {
            /** @var HashTag $tag */
            $tag = $this->findOneBy(['name' => $name]);
            if (!$tag) {
                $tag = new HashTag($name);
                $em->persist($tag);
                $entity->addHashTag($tag);
            } elseif (!$entity->getHashTags()->contains($tag)) {
                $entity->addHashTag($tag);
            }
        }

        if ($saveTagsInEntity) {
            $entity->setCachedHashTags($tags['original']);
        }

        $em->persist($entity);
        $em->flush($entity);
    }
}
