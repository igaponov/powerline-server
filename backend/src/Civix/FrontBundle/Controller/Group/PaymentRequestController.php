<?php

namespace Civix\FrontBundle\Controller\Group;

use Civix\FrontBundle\Controller\PaymentRequestController as Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/payment-request")
 */
class PaymentRequestController extends Controller
{
    public function getPaymentRequestFormClass()
    {
        if (!$this->isAvailableGroupSection()) {
            return '\Civix\FrontBundle\Form\Type\Poll\PaymentRequest';
        }

        return '\Civix\FrontBundle\Form\Type\Poll\PaymentRequestGroup';
    }
}
