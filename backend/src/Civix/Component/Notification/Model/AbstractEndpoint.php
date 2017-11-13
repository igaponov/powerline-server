<?php

namespace Civix\Component\Notification\Model;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\InheritanceType;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass = "Civix\CoreBundle\Repository\Notification\EndpointRepository")
 * @ORM\Table(name="notification_endpoints", indexes={
 *      @ORM\Index(name="token", columns={"token"})
 * })
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="type", type="string")
 * @DiscriminatorMap({
 *      "ios": "Civix\Component\Notification\Model\IOSEndpoint",
 *      "android": "Civix\Component\Notification\Model\AndroidEndpoint"
 * })
 * @Serializer\ExclusionPolicy("all")
 * @Serializer\Discriminator(field = "type", map = {
 *      "ios": "Civix\Component\Notification\Model\IOSEndpoint",
 *      "android": "Civix\Component\Notification\Model\AndroidEndpoint"
 * })
 */
abstract class AbstractEndpoint implements ModelInterface
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
     * @var RecipientInterface
     *
     * @ORM\ManyToOne(targetEntity="Civix\Component\Notification\Model\RecipientInterface")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    private $user;

    public static function getTypes(): array
    {
        return [
            self::TYPE_IOS,
            self::TYPE_ANDROID,
        ];
    }

    /**
     * @param string $arn
     *
     * @return self
     */
    public function setArn($arn): AbstractEndpoint
    {
        $this->arn = $arn;

        return $this;
    }

    /**
     * @return string
     */
    public function getArn(): ?string
    {
        return $this->arn;
    }

    /**
     * @param int $id
     *
     * @return self
     */
    public function setId($id): AbstractEndpoint
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param string $token
     *
     * @return self
     */
    public function setToken($token): AbstractEndpoint
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return string
     */
    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * @param RecipientInterface $user
     *
     * @return self
     */
    public function setUser(RecipientInterface $user): AbstractEndpoint
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return RecipientInterface
     */
    public function getUser(): ?RecipientInterface
    {
        return $this->user;
    }

    public function getContext(): array
    {
        return [
            'token' => $this->getToken(),
            'arn' => $this->getArn(),
            'user' => [
                'id' => $this->getUser() ? $this->getUser()->getId() : null,
                'username' => $this->getUser() ? $this->getUser()->getUsername() : null,
            ],
        ];
    }
}
