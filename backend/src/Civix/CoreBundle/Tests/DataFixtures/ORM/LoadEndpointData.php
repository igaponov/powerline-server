<?php

namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Notification\AndroidEndpoint;
use Civix\CoreBundle\Entity\Notification\IOSEndpoint;
use Civix\CoreBundle\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadEndpointData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        /** @var User $user1 */
        $user1 = $this->getReference('user_1');
        /** @var User $user2 */
        $user2 = $this->getReference('user_2');
        /** @var User $user3 */
        $user3 = $this->getReference('user_3');

        $endpoint = new AndroidEndpoint();
        $endpoint->setUser($user1)
            ->setToken('APA91bFebXXI8gh9J2sIdwjCg_jHJBv1e97rBfKlne9xG1JVC4xB9h8XTPuv-6IvtzaQA7LW-qBjK1CsGigHmOvVh_Er31TG-2TEY4Q78RECLthWQTGzf5w')
            ->setArn('arn:aws:sns:us-east-1:863632456175:endpoint/GCM/powerline_android_dev/a5c80552-d9e9-37d0-9e02-baab7e6cf2f2');
        $manager->persist($endpoint);

        $endpoint = new IOSEndpoint();
        $endpoint->setUser($user1)
            ->setToken('c00487af0057dcdd23d2dfce298561d5b5d7b8e50c9d387cd13c201f9c83003d')
            ->setArn('arn:aws:sns:us-east-1:863632456175:endpoint/APNS/powerline_ios_dev/120d6cca-bc7b-333b-8887-b8c69242b3f6');
        $manager->persist($endpoint);

        $endpoint = new AndroidEndpoint();
        $endpoint->setUser($user2)
            ->setToken('APA91bGoHjW2Ti8beudjqloj8jVbPrXwD_6d7ihbeof7HrcRakyqeAQ07ZSgE3WzSpXkB4c6_F-7-wg1S0P8twYlx-iwuMdbjiIxyGssdPgdwgZE6LOeI8k')
            ->setArn('arn:aws:sns:us-east-1:863632456175:endpoint/GCM/powerline_android_staging/d01c966c-e7a7-33e4-bec8-532f8e818daf');
        $manager->persist($endpoint);

        $endpoint = new IOSEndpoint();
        $endpoint->setUser($user3)
            ->setToken('1ec5197cd9813708119e591e8793f75ba7049c160baa8861e9d554e69528246d')
            ->setArn('arn:aws:sns:us-east-1:863632456175:endpoint/APNS/powerline_ios_staging/cf70b808-444d-390c-bb5b-f05ebe8aacfb');
        $manager->persist($endpoint);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadUserData::class];
    }
}
