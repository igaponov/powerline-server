<?php

namespace Tests\Civix\CoreBundle\Repository;

use Civix\ApiBundle\Tests\WebTestCase;
use Civix\CoreBundle\Entity\SocialActivity;
use Civix\CoreBundle\Entity\User;
use Tests\Civix\CoreBundle\DataFixtures\ORM\Issue\PS617;

class SocialActivityRepositoryTest extends WebTestCase
{
    public function testFindByRecipientAndType()
    {
        $repo = $this->loadFixtures([PS617::class])->getReferenceRepository();
        /** @var User $recipient */
        $recipient = $repo->getReference('user_3');
        $repository = $this->getContainer()->get('civix_core.social_activity_repository');
        $iterator = $repository->findByRecipientAndType($recipient, SocialActivity::TYPE_FOLLOW_REQUEST);
        $this->assertCount(1, iterator_to_array($iterator));
    }
}
