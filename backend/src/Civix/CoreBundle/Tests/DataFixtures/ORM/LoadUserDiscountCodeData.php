<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\DiscountCode;
use Civix\CoreBundle\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadUserDiscountCodeData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var User $user1 */
        $user1 = $this->getReference('user_1');
        /** @var User $user2 */
        $user2 = $this->getReference('user_2');
        /** @var DiscountCode $code2 */
        $code2 = $this->getReference('discount_code_2');
        /** @var DiscountCode $code3 */
        $code3 = $this->getReference('discount_code_3');

        $code2->use($user1);
        $manager->persist($code2);

        $code3->use($user1);
        $code3->use($user2);
        $manager->persist($code3);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadDiscountCodeData::class];
    }
}