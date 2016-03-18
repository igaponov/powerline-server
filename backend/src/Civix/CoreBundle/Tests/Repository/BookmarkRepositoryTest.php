<?php
namespace Civix\CoreBundle\Tests\Repository;

use Civix\CoreBundle\Entity\Bookmark;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Repository\BookmarkRepository;
use Civix\CoreBundle\Tests\DataFixtures\ORM\LoadUserData;
use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Liip\FunctionalTestBundle\Test\WebTestCase;

class BookmarkRepositoryTest extends WebTestCase
{
    public function testSave()
    {
        $user = $this->loadUser();

        /** @var BookmarkRepository $repo */
        $repo = $this->getContainer()->get('doctrine')->getRepository(Bookmark::class);
        $bookmark1 = $repo->save(Bookmark::TYPE_POST, $user, 1);
        $bookmark2 = $repo->save(Bookmark::TYPE_POST, $user, 1);
        $bookmark3 = $repo->save(Bookmark::TYPE_POLL, $user, 1);
        $bookmark4 = $repo->save(Bookmark::TYPE_POLL, $user, 2);

        $this->assertNotEmpty($bookmark1->getId());
        $this->assertEquals($bookmark1->getId(), $bookmark2->getId());
        $this->assertNotEquals($bookmark1->getId(), $bookmark3->getId());
        $this->assertNotEquals($bookmark1->getId(), $bookmark4->getId());
    }

    public function testFindByType()
    {
        $user = $this->loadUser();

        /** @var BookmarkRepository $repo */
        $repo = $this->getContainer()->get('doctrine')->getRepository(Bookmark::class);

        $repo->save(Bookmark::TYPE_POST, $user, 1);
        $repo->save(Bookmark::TYPE_POST, $user, 1);
        $repo->save(Bookmark::TYPE_POLL, $user, 1);
        $repo->save(Bookmark::TYPE_POLL, $user, 2);

        $bookmarks1 = $repo->findByType(Bookmark::TYPE_ALL, $user, 1);
        $bookmarks2 = $repo->findByType(Bookmark::TYPE_POLL, $user, 1);
        $bookmarks3 = $repo->findByType(Bookmark::TYPE_PETITION, $user, 1);

        $this->assertCount(3, $bookmarks1['items']);
        $this->assertCount(2, $bookmarks2['items']);
        $this->assertCount(0, $bookmarks3['items']);
    }

    public function testDelete()
    {
        $user = $this->loadUser();

        /** @var BookmarkRepository $repo */
        $repo = $this->getContainer()->get('doctrine')->getRepository('CivixCoreBundle:Bookmark');

        $repo->save(Bookmark::TYPE_POST, $user, 1);
        $repo->save(Bookmark::TYPE_POST, $user, 1);
        $repo->save(Bookmark::TYPE_POLL, $user, 1);
        $repo->save(Bookmark::TYPE_POLL, $user, 2);

        $bookmarks = $repo->findByType(Bookmark::TYPE_ALL, $user, 1);

        $deleted = array();
        foreach($bookmarks['items'] as $item) {
            $deleted[] = $repo->delete($item->getId());
        }

        $this->assertCount(3, $deleted);
    }

    /**
     * @return User
     */
    private function loadUser()
    {
        /** @var AbstractExecutor $fixtures */
        $fixtures = $this->loadFixtures([LoadUserData::class]);
        $reference = $fixtures->getReferenceRepository();

        return $reference->getReference('testuserbookmark1');
    }
}

