<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Civix\CoreBundle\Entity\Group;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

/**
 * LoadGroupData.
 */
class LoadGroupData extends AbstractFixture implements ContainerAwareInterface, OrderedFixtureInterface
{
    const GROUP_NAME = 'testgroup';
    const GROUP_PASSWORD = 'testgroup7ZAPe3QnZhbdec';
    const GROUP_EMAIL = 'testgroup@example.com';

    /**
     * @var ContainerInterface
     */
    private $container;

    /** @var ObjectManager */
    private $manager;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        $this->manager = $manager;

        $this->addReference('group', $this->createGroup(self::GROUP_NAME, self::GROUP_PASSWORD));
        $this->addReference(
            'secret-group',
            $this->createGroup('testfollowsecretgroups', null, Group::GROUP_TRANSPARENCY_SECRET)
        );
        $this->addReference(
            'private-group',
            $this->createGroup('testfollowprivategroups', null, Group::GROUP_TRANSPARENCY_PRIVATE)
        );
    }

    /**
     * Get the order of this fixture
     *
     * @return integer
     */
    function getOrder()
    {
        return 12;
    }

    /**
     * @param $groupName
     * @param null $password
     * @param null $transparency
     * @return Group Group
     */
    private function createGroup($groupName, $password = null, $transparency = null)
    {
        $password = $password ?: $groupName;
        $transparency = $transparency ?: Group::GROUP_TRANSPARENCY_PUBLIC;

        $group = new Group();
        $group->setAcronym($groupName)
            ->setGroupType(Group::GROUP_TYPE_COMMON)
            ->setManagerEmail("$groupName@example.com")
            ->setManagerFirstName($groupName)
            ->setManagerLastName($groupName)
            ->setPassword($password)
            ->setTransparency($transparency)
            ->setUsername($groupName);

        /** @var PasswordEncoderInterface $encoder */
        $encoder = $this->container->get('security.encoder_factory')->getEncoder($group);
        $encodedPassword = $encoder->encodePassword($password, $group->getSalt());
        $group->setPassword($encodedPassword);

        $this->manager->persist($group);
        $this->manager->flush();

        return $group;
    }
}
