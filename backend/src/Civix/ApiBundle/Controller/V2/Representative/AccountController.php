<?php
namespace Civix\ApiBundle\Controller\V2\Representative;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\CoreBundle\Entity\Stripe\AccountRepresentative;
use Civix\CoreBundle\Service\StripeAccountManager;
use FOS\RestBundle\Controller\FOSRestController;
use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route("/representatives/{representative}/stripe-account")
 */
class AccountController extends FOSRestController
{
    /**
     * @var StripeAccountManager
     * @DI\Inject("civix_core.stripe_account_manager")
     */
    private $manager;

    /**
     * Delete representative's account
     *
     * @Route("")
     * @Method("DELETE")
     *
     * @ParamConverter("account", options={"mapping" = {"representative" = "representative"}})
     * @SecureParam("account", permission="edit")
     *
     * @ApiDoc(
     *     authentication=true,
     *     resource=true,
     *     section="Representatives",
     *     description="Delete representative's account",
     *     statusCodes={
     *         204="Success",
     *         404="Representative Account Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     * @param AccountRepresentative $account
     */
    public function deleteAction(AccountRepresentative $account)
    {
        $this->manager->deleteAccount($account);
    }
}