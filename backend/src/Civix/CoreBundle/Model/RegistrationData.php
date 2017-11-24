<?php

namespace Civix\CoreBundle\Model;

use libphonenumber\PhoneNumber;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber as AssertPhoneNumber;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"RegistrationData", "unique", "authy"})
 * @UniqueEntity(
 *     fields={"username", "email", "phone"},
 *     entityClass="Civix\CoreBundle\Entity\User",
 *     em="default",
 *     repositoryMethod="findByUsernameOrEmailOrPhone",
 *     groups={"unique"}
 * )
 */
class RegistrationData
{
    /**
     * @var string|null
     * @Assert\NotBlank()
     */
    public $firstName;

    /**
     * @var string|null
     * @Assert\NotBlank()
     */
    public $lastName;

    /**
     * @var string|null
     * @Assert\NotBlank()
     */
    public $username;

    /**
     * @var string|null
     * @Assert\NotBlank()
     * @Assert\Email()
     */
    public $email;

    /**
     * @var string|null
     * @Assert\NotBlank()
     * @Assert\Country()
     */
    public $country;

    /**
     * @var string|null
     * @Assert\NotBlank()
     */
    public $zip;

    /**
     * @var PhoneNumber|null
     * @Assert\NotBlank()
     * @AssertPhoneNumber()
     */
    public $phone;

    /**
     * @var string|null
     * @Assert\NotBlank()
     */
    public $code;
}