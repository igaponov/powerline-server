<?php

namespace Civix\CoreBundle\Model;

use Civix\CoreBundle\Validator\Constraints\AuthyCode;
use libphonenumber\PhoneNumber;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber as AssertPhoneNumber;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @AuthyCode(phoneAttribute="phone", codeAttribute="code", groups={"authy"})
 * @Assert\GroupSequence({"RegistrationData", "authy"})
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