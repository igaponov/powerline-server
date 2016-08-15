<?php
namespace Civix\CoreBundle\Tests\Repository;

use Civix\CoreBundle\Entity\Activity;
use Civix\CoreBundle\Entity\Bookmark;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Repository\BookmarkRepository;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadActivityData;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Liip\FunctionalTestBundle\Test\WebTestCase;

class BookmarkRepositoryTest extends WebTestCase
{
    /** @var User */
    private $user;

    /** @var BookmarkRepository */
    private $repo;

    /** @var  Bookmark */
    private $bookmark1;

    /** @var  Bookmark */
    private $bookmark2;

    /** @var  Bookmark */
    private $bookmark3;

    /** @var  Bookmark */
    private $bookmark4;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        /** @var AbstractExecutor $fixtures */
        $fixtures = $this->loadFixtures([LoadUserData::class, LoadActivityData::class]);
        $reference = $fixtures->getReferenceRepository();

        $this->user = $reference->getReference('testuserbookmark1');
        $petition = $reference->getReference('activity_petition');
        $microPetition = $reference->getReference('activity_micropetition');
        $question = $reference->getReference('activity_question');

        $this->repo = $this->getContainer()->get('doctrine')->getRepository(Bookmark::class);

        $this->bookmark1 = $this->repo->save(Activity::TYPE_USER_PETITION, $this->user, $microPetition->getId());
        $this->bookmark2 = $this->repo->save(Activity::TYPE_USER_PETITION, $this->user, $microPetition->getId());
        $this->bookmark3 = $this->repo->save(Activity::TYPE_QUESTION, $this->user, $question->getId());
        $this->bookmark4 = $this->repo->save(Activity::TYPE_PETITION, $this->user, $petition->getId());
    }

    protected function tearDown()
    {
        $this->user = null;
        $this->repo = null;
        $this->bookmark1 = null;
        $this->bookmark2 = null;
        $this->bookmark3 = null;
        $this->bookmark4 = null;
        parent::tearDown();
    }

    /**
     * @author Habibillah <habibillah@gmail.com>
     */
    public function testSave()
    {
        $this->assertNotEmpty($this->bookmark1->getId());
        $this->assertEquals($this->bookmark1->getId(), $this->bookmark2->getId());
        $this->assertNotEquals($this->bookmark1->getId(), $this->bookmark3->getId());
        $this->assertNotEquals($this->bookmark1->getId(), $this->bookmark4->getId());
    }

    /**
     * @author Habibillah <habibillah@gmail.com>
     */
    public function testFindByType()
    {
        $savedBookmarks1 = $this->repo->findByType(Activity::TYPE_ALL, $this->user, 1);
        $savedBookmarks2 = $this->repo->findByType(Activity::TYPE_LEADER_EVENT, $this->user, 1);
        $savedBookmarks3 = $this->repo->findByType(Activity::TYPE_PETITION, $this->user, 1);

        $this->assertCount(3, $savedBookmarks1['items']);
        $this->assertCount(0, $savedBookmarks2['items']);
        $this->assertCount(1, $savedBookmarks3['items']);
    }

    /**
     * @author Habibillah <habibillah@gmail.com>
     */
    public function testDelete()
    {
        $savedBookmarks = $this->repo->findByType(Activity::TYPE_ALL, $this->user, 1);

        $deleted = array();
        foreach($savedBookmarks['items'] as $item) {
            $deleted[] = $this->repo->delete($item->getId());
        }

        $this->assertCount(3, $deleted);
    }
}

