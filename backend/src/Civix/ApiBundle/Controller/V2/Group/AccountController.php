<?php
namespace Civix\ApiBundle\Controller\V2\Group;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Service\PaymentManager;
use FOS\RestBundle\Controller\FOSRestController;
use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route("/groups/{group}/stripe-account")
 */
class AccountController extends FOSRestController
{
    /**
     * @var PaymentManager
     * @DI\Inject("civix_core.payment_manager")
     */
    private $manager;

    /**
     * Delete group's account
     *
     * @Route("")
     * @Method("DELETE")
     *
     * @SecureParam("group", permission="edit")
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
     * @param Group $group
     */
    public function deleteAction(Group $group)
    {
        if ($account = $group->getStripeAccount()) {
            $this->manager->deleteAccount($account);
            return;
        }
        throw $this->createNotFoundException();
    }
}