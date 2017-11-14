<?php

namespace Civix\ApiBundle\Security\Core;

use Civix\Component\ContentConverter\ConverterInterface;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Model\TempFile;
use Civix\CoreBundle\Repository\UserRepository;
use Civix\CoreBundle\Service\User\UserManager;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use libphonenumber\PhoneNumber;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

class ApiUserProvider implements UserProviderInterface
{
    /**
     * @var UserRepository
     */
    private $repository;
    /**
     * @var UserManager
     */
    private $userManager;
    /**
     * @var array
     */
    protected $properties = array(
        'identifier' => 'id',
    );
    /**
     * @var ConverterInterface
     */
    private $converter;

    /**
     * @param UserRepository $repository
     * @param UserManager $userManager
     * @param ConverterInterface $converter
     * @param array $properties
     */
    public function __construct(
        UserRepository $repository,
        UserManager $userManager,
        ConverterInterface $converter,
        array $properties
    ) {
        $this->repository = $repository;
        $this->userManager = $userManager;
        $this->converter = $converter;
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
        $user = $this->repository->findOneBy(['token' => $token->getCredentials()]);
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
        $user = $this->repository->findOneBy(['email' => $email]);
        if (null !== $user) {
            $user->{'set'.ucfirst($property)}($username);
            $user->{'set'.ucfirst($propertySecret)}($response->getTokenSecret());
        } else {
            $user = $this->repository->findOneBy([$property => $username]);
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
            if ($response->getProfilePicture()) {
                $content = $this->converter->convert((string)$response->getProfilePicture());
                $user->setAvatar(new TempFile($content));
            }
        }

        $this->userManager->legacyRegister($user);

        return $user;
    }

    public function loadUserByPhone(PhoneNumber $phone)
    {
        return $this->repository->findOneByPhone($phone);
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
