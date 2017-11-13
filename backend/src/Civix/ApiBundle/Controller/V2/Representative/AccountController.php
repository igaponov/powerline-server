<?php
namespace Civix\ApiBundle\Controller\V2\Representative;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\CoreBundle\Entity\UserRepresentative;
use Civix\CoreBundle\Service\PaymentManager;
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
     * @var PaymentManager
     * @DI\Inject("civix_core.payment_manager")
     */
    private $manager;

    /**
     * Delete representative's account
     *
     * @Route("")
     * @Method("DELETE")
     *
     * @SecureParam("representative", permission="edit")
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
     * @param UserRepresentative $representative
     */
    public function deleteAction(UserRepresentative $representative)
    {
        if ($account = $representative->getStripeAccount()) {
            $this->manager->deleteAccount($account);
            return;
        }
        throw $this->createNotFoundException();
    }
}