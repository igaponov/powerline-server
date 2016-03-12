<?php
namespace Civix\CoreBundle\Service;

use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Intl\ResourceBundle\RegionBundleInterface;

class PhoneNumberNormalizer
{
    /**
     * @var PhoneNumberUtil
     */
    private $phoneNumberUtil;
    /**
     * @var RegionBundleInterface
     */
    private $regionBundle;

    public function __construct(
        PhoneNumberUtil $phoneNumberUtil,
        RegionBundleInterface $regionBundle
    ) {
        $this->phoneNumberUtil = $phoneNumberUtil;
        $this->regionBundle = $regionBundle;
    }

    /**
     * @param string $phone
     * @param string $country
     * @return string|void
     */
    public function normalize($phone, $country)
    {
        if (strlen($country) === 2) {
            $country = strtoupper($country);
        } elseif ($country) {
            $countries = $this->regionBundle->getCountryNames('en');
            $country = array_search($country, $countries);
        }
        if (!empty($country)) {
            $phoneNumber = $this->phoneNumberUtil->parse($phone, $country);
            $phone = $this->phoneNumberUtil->format($phoneNumber, PhoneNumberFormat::E164);
        }

        return $phone;
    }
}