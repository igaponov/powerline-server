<?php

namespace Civix\FrontBundle\Controller\Group;

use Civix\FrontBundle\Controller\PaymentController as Controller;

class PaymentController extends Controller
{
    public function getCustomerClass()
    {
        return '\Civix\CoreBundle\Entity\Customer\Customer';
    }
}
