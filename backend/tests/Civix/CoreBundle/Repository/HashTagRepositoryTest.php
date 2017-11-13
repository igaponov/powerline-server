<?php

namespace Tests\Civix\CoreBundle\Repository;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\HashTag;
use Civix\CoreBundle\Entity\HtmlBodyInterface;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadGroupPetitionData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\Group\LoadGroupQuestionData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadPostData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserPetitionData;

class HashTagRepositoryTest extends WebTestCase
{
    public function testAddForTaggableEntity()
    {
        $referenceRepository = $this->loadFixtures([
            LoadPostData::class,
            LoadGroupQuestionData::class,
            LoadGroupPetitionData::class,
            LoadUserPetitionData::class,
        ])->getReferenceRepository();
        $references = $this->getEntityReferences();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repository = $em->getRepository(HashTag::class);
        $flag = 0;
        foreach ($references as $key => $reference) {
            $hashTags = array_map(function($tag) use ($key) {
                return $tag . '-' . $key;
            }, ['#tagX', '#tagY', '#tagZ']);
            /** @var HtmlBodyInterface $entity */
            $entity = $referenceRepository->getReference($reference);
            $text = implode(' ', $hashTags);
            switch (true) {
                case $entity instanceof Question\Petition:
                    $entity->setPetitionBody($text);
                    $flag |= 1;
                    break;
                case $entity instanceof Question:
                    $entity->setSubject($text);
                    $flag |= 2;
                    break;
                case $entity instanceof Post:
                    $entity->setBody($text);
                    $flag |= 4;
                    break;
                case $entity instanceof UserPetition:
                    $entity->setBody($text);
                    $flag |= 8;
                    break;
                default:
                    $this->fail(sprintf('Unexpected class %s', get_class($entity)));
            }
            $repository->addForTaggableEntity($entity);
            $tags = array_map('strtolower', $hashTags);
            $this->assertSame($tags, $entity->getHashTags()->map(function (HashTag $tag) {
                return $tag->getName();
            })->toArray());
            $this->assertSame($tags, $entity->getCachedHashTags());
        }
        $this->assertSame(15, $flag, 'All entity types should be checked');
    }

    public function getEntityReferences(): array
    {
        return [
            'petition' => 'group_petition_1',
            'poll' => 'group_question_1',
            'post' => 'post_1',
            'user_petition' => 'user_petition_1',
        ];
    }
}
