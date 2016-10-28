<?php

namespace Civix\ApiBundle\Security\Core;

use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Service\CropAvatar;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\EntityManager;

class ApiUserProvider implements UserProviderInterface, OAuthAwareUserProviderInterface
{
    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var CropAvatar
     */
    private $cropAvatar;
    /**
     * @var array
     */
    protected $properties = array(
        'identifier' => 'id',
    );

    /**
     * @param EntityManager $em
     * @param CropAvatar $cropAvatar
     * @param array $properties
     */
    public function __construct(EntityManager $em, CropAvatar $cropAvatar, array $properties)
    {
        $this->em = $em;
        $this->cropAvatar = $cropAvatar;
        $this->properties = array_merge($this->properties, $properties);
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByUsername($username)
    {
        throw new \LogicException('This provider cannot load user by username.');
    }

    /**
     * {@inheritdoc}
     */
    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof UserInterface) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $user;
    }

    public function loadUserByToken(TokenInterface $token)
    {
        $user = $this->em->getRepository('CivixCoreBundle:User')
                ->findOneBy(['token' => $token->getCredentials()]);
        if (!$user) {
            throw new UsernameNotFoundException(sprintf("User not found."));
        }

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {
        return $class === User::class;
    }

    public function loadUserByOAuthUserResponse(UserResponseInterface $response)
    {
        $username = $response->getUsername();
        $email = $response->getEmail();
        $resourceOwnerName = $response->getResourceOwner()->getName();
        $property = $this->getProperty($resourceOwnerName);
        $propertySecret = $this->getProperty($resourceOwnerName.'_secret');
        $repository = $this->em->getRepository(User::class);
        $user = $repository->findOneBy(['email' => $email]);
        if (null !== $user) {
            $user->{'set'.ucfirst($property)}($username);
            $user->{'set'.ucfirst($propertySecret)}($response->getTokenSecret());
        } else {
            $user = $repository->findOneBy([$property => $username]);
        }
        if (null === $user || null === $username) {
            if (!$response->getEmail()) {
                throw new \RuntimeException(sprintf(
                    'Please make sure that verified email is connected to your %s account.',
                    ucfirst($resourceOwnerName)
                ), 400);
            }
            $user = new User();
            $user->setUsername($response->getEmail());
            $user->setEmail($response->getEmail());
            if ($response->getFirstName()) {
                $user->setFirstName($response->getFirstName());
                $user->setLastName($response->getLastName());
            } else {
                $name = explode(' ', $response->getRealName());
                $user->setFirstName($name[0]);
                $user->setLastName(isset($name[1]) ? $name[1] : '');
            }
            $user->setPassword(sha1(uniqid('pass', true)));
            $user->{'set'.ucfirst($property)}($username);
            $user->{'set'.ucfirst($propertySecret)}($response->getTokenSecret());
            $this->cropAvatar->saveSquareAvatarFromPath($user, $response->getProfilePicture());
        }
        $user->generateToken();
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /**
     * Gets the property for the response.
     *
     * @param string $resourceOwnerName
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    protected function getProperty($resourceOwnerName)
    {
        if (!isset($this->properties[$resourceOwnerName])) {
            throw new \RuntimeException(sprintf("No property defined for entity for resource owner '%s'.", $resourceOwnerName));
        }

        return $this->properties[$resourceOwnerName];
    }
}
