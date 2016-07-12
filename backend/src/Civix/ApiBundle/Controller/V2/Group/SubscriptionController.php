<?php

namespace Civix\ApiBundle\Controller\V2\Group;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\ApiBundle\Form\Type\SubscriptionType;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Subscription\Subscription;
use Civix\CoreBundle\Service\Subscription\SubscriptionManager;
use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SubscriptionController
 * @package Civix\ApiBundle\Controller
 *
 * @Route("/groups/{group}/subscription")
 */
class SubscriptionController extends Controller
{
    /**
     * @var SubscriptionManager
     * @DI\Inject("civix_core.subscription_manager")
     */
    private $manager;

    /**
     * Return user's subscription
     *
     * @Route("")
     * @Method("GET")
     *
     * @SecureParam("group", permission="edit")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Return subscription",
     *     output="Civix\CoreBundle\Entity\Subscription\Subscription",
     *     statusCodes={
     *         403="Access Denied",
     *         404="Group Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param Group $group
     *
     * @return Subscription
     */
    public function getAction(Group $group)
    {
        return $this->manager->getSubscription($group);
    }

    /**
     * Update user's subscription
     *
     * @Route("")
     * @Method("PUT")
     *
     * @SecureParam("group", permission="edit")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Update subscription",
     *     input="Civix\ApiBundle\Form\Type\SubscriptionType",
     *     output="Civix\CoreBundle\Entity\Subscription\Subscription",
     *     statusCodes={
     *         400="Bad Request",
     *         403="Access Denied",
     *         404="Group Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param Request $request
     * @param Group $group
     *
     * @return Subscription|\Symfony\Component\Form\Form
     */
    public function putAction(Request $request, Group $group)
    {
        $subscription = $this->manager->getSubscription($group);
        $form = $this->createForm(new SubscriptionType, $subscription);
        $form->submit($request, true);
        
        if ($form->isValid()) {
            return $this->manager->subscribe($form->getData());
        }
        
        return $form;
    }

    /**
     * Delete user's subscription
     *
     * @Route("")
     * @Method("DELETE")
     *
     * @SecureParam("group", permission="edit")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Delete subscription",
     *     output="Civix\CoreBundle\Entity\Subscription\Subscription"
     * )
     *
     * @param Group $group
     *
     * @return Subscription
     */
    public function deleteAction(Group $group)
    {
        $subscription = $this->manager->getSubscription($group);
        return $this->manager->unsubscribe($subscription);
    }
}
