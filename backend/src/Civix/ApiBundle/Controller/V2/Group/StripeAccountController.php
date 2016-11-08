<?php
namespace Civix\ApiBundle\Controller\V2\Group;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\CoreBundle\Entity\Stripe\AccountGroup;
use Civix\CoreBundle\Service\StripeAccountManager;
use FOS\RestBundle\Controller\FOSRestController;
use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route("/stripe-accounts")
 */
class StripeAccountController extends FOSRestController
{
    /**
     * @var StripeAccountManager
     * @DI\Inject("civix_core.stripe_account_manager")
     */
    private $manager;

    /**
     * Delete group's account
     *
     * @Route("/{id}")
     * @Method("DELETE")
     *
     * @SecureParam("account", permission="edit")
     *
     * @ApiDoc(
     *     authentication=true,
     *     resource=true,
     *     section="Groups",
     *     description="Delete group's account",
     *     statusCodes={
     *         204="Success",
     *         404="Group Account Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     * @param AccountGroup $account
     */
    public function deleteAction(AccountGroup $account)
    {
        $this->manager->deleteAccount($account);
    }
}