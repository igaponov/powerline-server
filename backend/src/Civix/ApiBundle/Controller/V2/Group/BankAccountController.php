<?php

namespace Civix\ApiBundle\Controller\V2\Group;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\ApiBundle\Controller\BaseController;
use Civix\ApiBundle\Form\Type\BankAccountType;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Stripe\Account;
use Civix\CoreBundle\Entity\Stripe\AccountGroup;
use Civix\CoreBundle\Entity\Stripe\BankAccount;
use Civix\CoreBundle\Service\StripeAccountManager;
use FOS\RestBundle\Controller\Annotations\View;
use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/groups/{group}/bank-accounts")
 */
class BankAccountController extends BaseController
{
    /**
     * @var StripeAccountManager
     * @DI\Inject("civix_core.stripe_account_manager")
     */
    private $manager;

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
        $form = $this->createForm(new BankAccountType());
        $form->submit($request);

        if ($form->isValid()) {
            return $this->manager->addBankAccount($group, $form->getData());
        }

        return $form;
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
        /* @var Account $account */
        $account = $this->getDoctrine()
            ->getRepository(AccountGroup::class)
            ->findOneBy(['user' => $group]);

        if ($account) {
            return $account->getBankAccounts();
        }

        return [];
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
        /* @var AccountGroup $account */
        $account = $this->getDoctrine()
            ->getRepository(AccountGroup::class)
            ->findOneBy(['user' => $group]);
        if ($account) {
            $bankAccount = new BankAccount();
            $bankAccount->setId($id);
            $this->manager->deleteBankAccount($account, $bankAccount);
        }
    }
}
