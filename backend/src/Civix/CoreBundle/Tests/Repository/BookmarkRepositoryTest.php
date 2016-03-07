<?php
namespace Civix\CoreBundle\Tests\Repository;

use Civix\CoreBundle\Entity\Bookmark;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Repository\BookmarkRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BookmarkRepositoryTest extends WebTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /** @var  User $user */
    private $user;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->user = $this->em
            ->getRepository('CivixCoreBundle:User')
            ->findOneBy(array('username' => 'testuser1'));

        if ($this->user === null) {
            $this->user = new User();
            $this->user->setUsername('testuser1')
                ->setEmail('habibillah@gmail.com')
                ->setPassword('testuser1')
                ->setToken('testuser1')
                ->setBirth(new \DateTime())
                ->setDoNotDisturb(true)
                ->setIsNotifDiscussions(false)
                ->setIsNotifMessages(false)
                ->setIsRegistrationComplete(true)
                ->setIsNotifOwnPostChanged(false);

            $this->em->persist($this->user);
            $this->em->flush();
        }
    }

    public function testSave()
    {
        /** @var BookmarkRepository $repo */
        $repo = $this->em->getRepository('CivixCoreBundle:Bookmark');
        $bookmark1 = $repo->save(Bookmark::TYPE_POST, $this->user, 1);
        $bookmark2 = $repo->save(Bookmark::TYPE_POST, $this->user, 1);
        $bookmark3 = $repo->save(Bookmark::TYPE_POLL, $this->user, 1);
        $bookmark4 = $repo->save(Bookmark::TYPE_POLL, $this->user, 2);

        $this->assertNotEmpty($bookmark1->getId());
        $this->assertEquals($bookmark1->getId(), $bookmark2->getId());
        $this->assertNotEquals($bookmark1->getId(), $bookmark3->getId());
        $this->assertNotEquals($bookmark1->getId(), $bookmark4->getId());
    }

    public function testFindByType()
    {
        /** @var BookmarkRepository $repo */
        $repo = $this->em->getRepository('CivixCoreBundle:Bookmark');
        $bookmarks1 = $repo->findByType(Bookmark::TYPE_ALL, $this->user, 1);
        $bookmarks2 = $repo->findByType(Bookmark::TYPE_POLL, $this->user, 1);
        $bookmarks3 = $repo->findByType(Bookmark::TYPE_PETITION, $this->user, 1);

        $this->assertCount(3, $bookmarks1['items']);
        $this->assertCount(2, $bookmarks2['items']);
        $this->assertCount(0, $bookmarks3['items']);
    }

    public function testDelete()
    {
        /** @var BookmarkRepository $repo */
        $repo = $this->em->getRepository('CivixCoreBundle:Bookmark');
        $bookmarks = $repo->findByType(Bookmark::TYPE_ALL, $this->user, 1);
        $totalBookmark = count($bookmarks['items']);

        $deleted = array();
        foreach($bookmarks['items'] as $item) {
            $deleted[] = $repo->delete($item->getId());
        }

        $this->assertCount($totalBookmark, $deleted);
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        $this->em->remove($this->user);
        $this->em->flush();

        $this->em->close();

        parent::tearDown();
    }
}

