<?php

namespace Civix\CoreBundle\Entity;

use Civix\CoreBundle\Model\Avatar\DefaultAvatarInterface;
use Civix\CoreBundle\Model\Avatar\FirstLetterDefaultAvatar;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use JMS\Serializer\Annotation as Serializer;
use Civix\CoreBundle\Serializer\Type\Avatar;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Superuser Entity.
 *
 * @ORM\Table(name="superusers")
 * @ORM\Entity()
 *  
 * @UniqueEntity(fields={"username"}, groups={"registration"})
 */
class Superuser implements UserInterface, HasAvatarInterface, PasswordEncodeInterface
{
    const DEFAULT_AVATAR = '/bundles/civixfront/img/default_superuser.jpg';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="username", type="string", length=255, nullable=true, unique=true)
     *
     * @var string
     */
    private $username;

    /**
     * @ORM\Column(name="password", type="string", length=255, nullable=true)
     *
     * @var string
     */
    private $password;

    /**
     * @ORM\Column(name="salt", type="string", length=255, nullable=true)
     *
     * @var string
     */
    private $salt;

    /**
     * @ORM\Column(name="email", type="string", length=255)
     */
    private $email;

    /**
     * @var File
     */
    private $avatar;

    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"api-activities", "api-poll","api-groups"})
     * @Serializer\Type("Avatar")
     * @Serializer\Accessor(getter="getAvatarSrc")
     *
     * @var string
     */
    private $avatarFilePath;

    /**
     * @var string
     */
    private $avatarSrc;

    /**
     * @Serializer\Expose()
     * @Serializer\ReadOnly()
     * @Serializer\Groups({"api-activities", "api-poll"})
     */
    private $type = 'admin';

    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"api-activities", "api-poll","api-groups"})
     * @Serializer\SerializedName("official_title")
     */
    private $officialTitle = 'The Global Forum';

    /**
     * @var string
     * @Serializer\Expose()
     * @Serializer\Groups({"api-session"})
     * @ORM\Column(name="token", type="string", length=255, nullable=true)
     */
    private $token;

    /**
     * @var string
     */
    private $plainPassword;
    
    public function __construct()
    {
        $this->salt = base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set username.
     *
     * @param string $username
     *
     * @return Superuser
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Set password.
     *
     * @param string $password
     *
     * @return Superuser
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password.
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set salt.
     *
     * @param string $salt
     *
     * @return Superuser
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;

        return $this;
    }

    /**
     * Get salt.
     *
     * @return string
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set email.
     *
     * @param string $email
     *
     * @return Superuser
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get user Roles.
     *
     * @return array
     */
    public function getRoles()
    {
        return array('ROLE_SUPERUSER');
    }

    /**
     * Erase credentials.
     */
    public function eraseCredentials()
    {
    }

    /**
     * Get officialName.
     *
     * @return string
     */
    public function getOfficialName()
    {
        return $this->officialTitle;
    }

    /**
     * Get avatar.
     *
     * @return string
     */
    public function getAvatar()
    {
        return $this->avatar;
    }

    /**
     * Get default avatar.
     *
     * @return DefaultAvatarInterface
     */
    public function getDefaultAvatar(): DefaultAvatarInterface
    {
        return new FirstLetterDefaultAvatar($this->officialTitle);
    }

    /**
     * Get avatarSrc.
     *
     * @return Avatar
     */
    public function getAvatarSrc()
    {
        return new Avatar($this);
    }

    public function getType()
    {
        return 'superuser';
    }
    
    /**
     * Set token.
     *
     * @param string $token
     *
     * @return $this
     */
    public function setToken($token)
    {
    	$this->token = $token;
    
    	return $this;
    }
    
    /**
     * Get token.
     *
     * @return string
     */
    public function getToken()
    {
    	return $this->token;
    }
    
    public function generateToken()
    {
    	$bytes = false;
    	if (function_exists('openssl_random_pseudo_bytes') && 0 !== stripos(PHP_OS, 'win')) {
    		$bytes = openssl_random_pseudo_bytes(32, $strong);
    
    		if (true !== $strong) {
    			$bytes = false;
    		}
    	}
    
    	if (false === $bytes) {
    		$bytes = hash('sha256', uniqid(mt_rand(), true), true);
    	}
    
    	$this->setToken(base_convert(bin2hex($bytes), 16, 36).$this->getId());
    }

    public function setAvatar(File $avatar)
    {

    }

    public function setAvatarFileName($avatarFileName)
    {

    }

    public function getAvatarFileName()
    {

    }

    /**
     * @return string
     */
    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    /**
     * @param string $plainPassword
     * @return Superuser
     */
    public function setPlainPassword($plainPassword)
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }
}
