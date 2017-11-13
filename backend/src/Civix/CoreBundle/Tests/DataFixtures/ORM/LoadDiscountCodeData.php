<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\DiscountCode;
use Civix\CoreBundle\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadDiscountCodeData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var User $user1 */
        $user1 = $this->getReference('user_1');
        /** @var User $user2 */
        $user2 = $this->getReference('user_2');
        /** @var User $user3 */
        $user3 = $this->getReference('user_3');

        $code = new DiscountCode('1MONTH', $user1);
        $manager->persist($code);
        $this->addReference('discount_code_1', $code);

        $code = new DiscountCode('REWARD');
        $manager->persist($code);
        $this->addReference('discount_code_2', $code);

        $code = new DiscountCode('3MONTH', $user3);
        $manager->persist($code);
        $this->addReference('discount_code_3', $code);

        $code = new DiscountCode('6MONTH', $user2);
        $manager->persist($code);
        $this->addReference('discount_code_4', $code);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadUserData::class];
    }
}