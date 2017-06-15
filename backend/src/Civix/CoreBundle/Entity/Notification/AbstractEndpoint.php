<?php

namespace Civix\CoreBundle\Entity\Notification;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use JMS\Serializer\Annotation as Serializer;
use Civix\CoreBundle\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass = "Civix\CoreBundle\Repository\Notification\EndpointRepository")
 * @ORM\Table(name="notification_endpoints", indexes={
 *      @ORM\Index(name="token", columns={"token"})
 * })
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="type", type="string")
 * @DiscriminatorMap({
 *      "ios" = "Civix\CoreBundle\Entity\Notification\IOSEndpoint",
 *      "android" = "Civix\CoreBundle\Entity\Notification\AndroidEndpoint"
 * })
 * @Serializer\ExclusionPolicy("all")
 * @Serializer\Discriminator(field = "type", map = {
 *      "ios": "Civix\CoreBundle\Entity\Notification\IOSEndpoint",
 *      "android": "Civix\CoreBundle\Entity\Notification\AndroidEndpoint",
 * })
 */
abstract class AbstractEndpoint
{
    const TYPE_IOS = 'ios';
    const TYPE_ANDROID = 'android';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose()
     * @Serializer\Groups({"owner-get"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string", length=255, unique=true)
     * @Serializer\Expose()
     * @Serializer\Groups({"owner-get", "owner-create"})
     *
     * @Assert\NotBlank()
     */
    private $token;

    /**
     * @var string
     *
     * @ORM\Column(name="arn", type="string", length=255)
     */
    private $arn;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Civix\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    private $user;

    public static function getTypes()
    {
        return [
            self::TYPE_IOS,
            self::TYPE_ANDROID,
        ];
    }

    abstract public function getPlatformMessage($title, $message, $type, $entityData, $image, $badge = null);

    /**
     * @param string $arn
     *
     * @return self
     */
    public function setArn($arn)
    {
        $this->arn = $arn;

        return $this;
    }

    /**
     * @return string
     */
    public function getArn()
    {
        return $this->arn;
    }

    /**
     * @param int $id
     *
     * @return self
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $token
     *
     * @return self
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param User $user
     *
     * @return self
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    public function getContext()
    {
        return [
            'token' => $this->getToken(),
            'arn' => $this->getArn(),
            'user' => [
                'id' => $this->getUser()->getId(),
                'username' => $this->getUser()->getUsername(),
            ],
        ];
    }
}
