<?php

namespace Civix\ApiBundle\Controller\V2\Group;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\ApiBundle\Controller\V2\AbstractBankAccountController;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Stripe\Account;
use Civix\CoreBundle\Service\PaymentManager;
use FOS\RestBundle\Controller\Annotations\View;
use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/groups/{group}/bank-accounts")
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
     * @SecureParam("group", permission="edit")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Add bank account",
     *     input="Civix\ApiBundle\Form\Type\BankAccountType",
     *     statusCodes={
     *         400="Bad Request",
     *         403="Access Denied",
     *         404="Group Not Found",
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
     * @param Group $group
     *
     * @return Account|\Symfony\Component\Form\Form
     */
    public function postBankAccountAction(Request $request, Group $group)
    {
        return $this->postBankAccount($request, $group);
    }

    /**
     * @Route("")
     * @Method("GET")
     *
     * @SecureParam("group", permission="edit")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
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
     *         404="Group Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api"})
     *
     * @param Group $group
     *
     * @return array|mixed
     */
    public function getBankAccountsAction(Group $group)
    {
        return $this->getBankAccounts($group);
    }

    /**
     * @Route("/{id}", requirements={"id" = ".+"})
     * @Method("DELETE")
     *
     * @SecureParam("group", permission="edit")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Delete group's stripe account",
     *     statusCodes={
     *         204="Success",
     *         403="Access Denied",
     *         404="Group Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param Group $group
     * @param string $id
     */
    public function deleteBankAccountAction(Group $group, $id)
    {
        $this->deleteBankAccount($group, $id);
    }
}
