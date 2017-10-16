<?php

namespace Civix\ApiBundle\Controller\V2\Representative;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\ApiBundle\Controller\V2\AbstractBankAccountController;
use Civix\CoreBundle\Entity\UserRepresentative;
use Civix\CoreBundle\Entity\Stripe\Account;
use Civix\CoreBundle\Service\PaymentManager;
use FOS\RestBundle\Controller\Annotations\View;
use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/representatives/{representative}/bank-accounts")
 */
class BankAccountController extends AbstractBankAccountController
{
    /**
     * @var PaymentManager
     * @DI\Inject("civix_core.payment_manager")
     */
    private $manager;

    protected function getManager()
    {
        return $this->manager;
    }

    /**
     * @Route("")
     * @Method("POST")
     *
     * @SecureParam("representative", permission="edit")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Representatives",
     *     description="Add bank account",
     *     input="Civix\ApiBundle\Form\Type\BankAccountType",
     *     statusCodes={
     *         400="Bad Request",
     *         403="Access Denied",
     *         404="Representative Not Found",
     *         405="Method Not Allowed"
     *     },
     *     responseMap={
     *          201={
     *              "class" = "Civix\CoreBundle\Entity\Stripe\Account",
     *              "parsers" = {
     *                  "Nelmio\ApiDocBundle\Parser\CollectionParser",
     *                  "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *              }
     *          }
     *     }
     * )
     *
     * @View(statusCode=201)
     *
     * @param Request $request
     * @param UserRepresentative $representative
     *
     * @return Account|\Symfony\Component\Form\Form
     */
    public function postBankAccountAction(Request $request, UserRepresentative $representative)
    {
        return $this->postBankAccount($request, $representative);
    }

    /**
     * @Route("")
     * @Method("GET")
     *
     * @SecureParam("representative", permission="edit")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Representatives",
     *     description="Get bank accounts",
     *     output={
     *          "class" = "array<Civix\CoreBundle\Entity\Stripe\BankAccount>",
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\CollectionParser",
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         403="Access Denied",
     *         404="Representative Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api"})
     *
     * @param UserRepresentative $representative
     *
     * @return array|mixed
     */
    public function getBankAccountsAction(UserRepresentative $representative)
    {
        return $this->getBankAccounts($representative);
    }

    /**
     * @Route("/{id}", requirements={"id" = ".+"})
     * @Method("DELETE")
     *
     * @SecureParam("representative", permission="edit")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Representatives",
     *     description="Delete representative's stripe account",
     *     statusCodes={
     *         204="Success",
     *         403="Access Denied",
     *         404="Representative Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param UserRepresentative $representative
     * @param string $id
     */
    public function deleteBankAccountAction(UserRepresentative $representative, $id)
    {
        $this->deleteBankAccount($representative, $id);
    }
}
