<?php

namespace Civix\ApiBundle\Controller\Group;

use Civix\ApiBundle\Form\Type\SubscriptionType;
use Civix\CoreBundle\Entity\Subscription\Subscription;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SubscriptionController
 * @package Civix\ApiBundle\Controller
 *
 * @Route("/subscription")
 */
class SubscriptionController extends Controller
{
    /**
     * Return user's subscription
     *
     * @Route("", name="civix_get_subscription")
     * @Method("GET")
     *
     * @ApiDoc(
     *     section="User Management",
     *     description="Return subscription",
     *     output="Civix\CoreBundle\Entity\Subscription\Subscription"
     * )
     *
     * @return Subscription|\Symfony\Component\Form\Form
     */
    public function getAction()
    {
        $subscriptionManager = $this->get('civix_core.subscription_manager');

        return $subscriptionManager->getSubscription($this->getUser());
    }
    
    /**
     * Update user's subscription
     *
     * @Route("", name="civix_put_subscription")
     * @Method("PUT")
     *
     * @ApiDoc(
     *     section="User Management",
     *     description="Update subscription",
     *     input="Civix\ApiBundle\Form\Type\SubscriptionType",
     *     output="Civix\CoreBundle\Entity\Subscription\Subscription"
     * )
     *
     * @param Request $request
     * @return Subscription|\Symfony\Component\Form\Form
     */
    public function putAction(Request $request)
    {
        $subscriptionManager = $this->get('civix_core.subscription_manager');

        $subscription = $subscriptionManager->getSubscription($this->getUser());
        
        $form = $this->createForm(new SubscriptionType, $subscription);
        $form->submit($request);
        
        if ($form->isValid()) {
            return $subscriptionManager->subscribe($form->getData());
        }
        
        return $form;
    }

    /**
     * Delete user's subscription
     *
     * @Route("", name="civix_delete_subscription")
     * @Method("DELETE")
     *
     * @ApiDoc(
     *     section="User Management",
     *     description="Delete subscription",
     *     output="Civix\CoreBundle\Entity\Subscription\Subscription"
     * )
     *
     * @return Subscription
     */
    public function deleteAction()
    {
        $subscriptionManager = $this->get('civix_core.subscription_manager');

        $subscription = $subscriptionManager->getSubscription($this->getUser());
        return $subscriptionManager->unsubscribe($subscription);
    }
}
