<?php

namespace Tests\Civix\CoreBundle\Service\ThumbnailGenerator;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Model\FacebookContent;
use Civix\CoreBundle\Service\ThumbnailGenerator\UserContentToFacebookContentNormalizer;
use Faker\Factory;
use Imgix\UrlBuilder;
use PHPUnit\Framework\TestCase;
use Vich\UploaderBundle\Storage\StorageInterface;

class UserContentToFacebookContentNormalizerTest extends TestCase
{
    public function testSupports()
    {
        $storage = $this->createMock(StorageInterface::class);
        $urlBuilder = $this->getUrlBuilderMock();
        $normalizer = new UserContentToFacebookContentNormalizer($storage, $urlBuilder);
        $this->assertTrue($normalizer->supports(new Post()));
    }

    /**
     * @param Post|UserPetition $userContent
     *
     * @dataProvider getUserContent
     */
    public function testNormalize($userContent)
    {
        $faker = Factory::create();
        $user = (new User())
            ->setFirstName($faker->firstName)
            ->setLastName($faker->lastName)
            ->setUsername($faker->userName)
            ->setAvatarFileName($faker->imageUrl());
        $group = (new Group())
            ->setOfficialName($faker->company)
            ->setAvatarFileName($faker->imageUrl());
        $userContent
            ->setUser($user)
            ->setGroup($group)
            ->setBody($faker->text);
        $userUrl = $faker->imageUrl();
        $groupUrl = $faker->imageUrl();
        $storage = $this->createMock(StorageInterface::class);
        $storage->expects($this->exactly(2))
            ->method('resolveUri')
            ->withConsecutive([$user], [$group])
            ->willReturnOnConsecutiveCalls($userUrl, $groupUrl);
        $urlBuilder = $this->getUrlBuilderMock(['createURL']);
        $urlBuilder->expects($this->exactly(2))
            ->method('createURL')
            ->withConsecutive([$userUrl], [$groupUrl])
            ->willReturnOnConsecutiveCalls($userUrl, $groupUrl);
        $normalizer = new UserContentToFacebookContentNormalizer($storage, $urlBuilder);
        $content = $normalizer->normalize($userContent);
        $this->assertInstanceOf(FacebookContent::class, $content);
        $this->assertSame($user->getFirstName().' '.$user->getLastName()[0].'.', $content->getUserFullName());
        $this->assertSame($user->getUsername(), $content->getUsername());
        $this->assertSame($userUrl, $content->getUserAvatar());
        $this->assertSame($group->getOfficialName(), $content->getGroupName());
        $this->assertSame($groupUrl, $content->getGroupAvatar());
        $this->assertSame($userContent->getBody(), $content->getText());
    }

    public function getUserContent()
    {
        return [
            [new Post()],
            [new UserPetition()],
        ];
    }

    /**
     * @param array $methods
     * @return \PHPUnit_Framework_MockObject_MockObject|UrlBuilder
     */
    private function getUrlBuilderMock(array $methods = []): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->getMockBuilder(UrlBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }
}
