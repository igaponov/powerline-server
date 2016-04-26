<?php

namespace Civix\ApiBundle\Controller\Group;

use Civix\ApiBundle\Form\Type\Group\PaymentAccountSettingsType;
use Civix\FrontBundle\Form\Model\PaymentAccountSettings;
use FOS\RestBundle\Controller\Annotations\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class PaymentAccountSettingsController
 * @package Civix\ApiBundle\Controller
 * 
 * @Route("/payment-settings")
 */
class PaymentAccountSettingsController extends Controller
{
    /**
     * Return group's payment settings
     *
     * @Route("", name="civix_get_group_payment_account_settings")
     * @Method("GET")
     *
     * @ApiDoc(
     *     section="User Management",
     *     description="Return group's payment settings",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Customer\Customer",
     *          "groups" = {"payment-settings"}
     *     }
     * )
     *
     * @View(serializerGroups={"payment-settings"})
     * 
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function getAction()
    {
        $customerManager = $this->get('civix_core.customer_manager');
        return $customerManager->getCustomerByUser($this->getUser());
    }
    
    /**
     * Update group's payment settings
     *
     * @Route("", name="civix_put_group_payment_account_settings")
     * @Method("PUT")
     *
     * @ApiDoc(
     *     section="User Management",
     *     description="Update group's payment settings",
     *     input="Civix\ApiBundle\Form\Type\Group\PaymentAccountSettingsType",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Customer\Customer",
     *          "groups" = {"payment-settings"}
     *     }
     * )
     *
     * @View(serializerGroups={"payment-settings"})
     * 
     * @param Request $request
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function putAction(Request $request)
    {
        $customerManager = $this->get('civix_core.customer_manager');
        $customer = $customerManager->getCustomerByUser($this->getUser());
        $data = new PaymentAccountSettings();
        $form = $this->createForm(new PaymentAccountSettingsType(), $data);
        $form->submit($request);
        if ($form->isValid()) {
            return $customerManager
                ->updateCustomerSettings($customer, $form->getData());
        }

        return $form;
    }
}
