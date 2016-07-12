<?php

namespace Civix\ApiBundle\Controller\V2\Group;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\ApiBundle\Form\Type\Group\PaymentAccountSettingsType;
use Civix\CoreBundle\Entity\Customer\Customer;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Service\Customer\CustomerManager;
use FOS\RestBundle\Controller\Annotations\View;
use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class PaymentAccountSettingsController
 * @package Civix\ApiBundle\Controller
 * 
 * @Route("/groups/{group}/payment-settings")
 */
class PaymentAccountSettingsController extends Controller
{
    /**
     * @var CustomerManager
     * @DI\Inject("civix_core.customer_manager")
     */
    private $manager;

    /**
     * Return group's payment settings
     *
     * @Route("")
     * @Method("GET")
     *
     * @SecureParam("group", permission="manage")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Return group's payment settings",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Customer\Customer",
     *          "groups" = {"payment-settings"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         403="Access Denied",
     *         404="Group Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"payment-settings"})
     *
     * @param Group $group
     *
     * @return Customer
     */
    public function getAction(Group $group)
    {
        return $this->manager->getCustomerByUser($group);
    }

    /**
     * Update group's payment settings
     *
     * @Route("")
     * @Method("PUT")
     *
     * @SecureParam("group", permission="manage")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Update group's payment settings",
     *     input="Civix\ApiBundle\Form\Type\Group\PaymentAccountSettingsType",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Customer\Customer",
     *          "groups" = {"payment-settings"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         400="Bad Request",
     *         403="Access Denied",
     *         404="Group Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"payment-settings"})
     *
     * @param Request $request
     * @param Group $group
     *
     * @return Customer|\Symfony\Component\Form\Form
     */
    public function putAction(Request $request, Group $group)
    {
        $customer = $this->manager->getCustomerByUser($group);
        $form = $this->createForm(new PaymentAccountSettingsType());
        $form->submit($request);

        if ($form->isValid()) {
            return $this->manager->updateCustomerSettings($customer, $form->getData());
        }

        return $form;
    }
}
