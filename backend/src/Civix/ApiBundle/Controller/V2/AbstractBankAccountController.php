<?php

namespace Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Controller\BaseController;
use Civix\ApiBundle\Form\Type\BankAccountType;
use Civix\CoreBundle\Entity\LeaderContentRootInterface;
use Civix\CoreBundle\Entity\Stripe\BankAccount;
use Civix\CoreBundle\Service\PaymentManager;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractBankAccountController extends BaseController
{
    /**
     * @return PaymentManager
     */
    abstract protected function getManager();

    public function postBankAccount(Request $request, LeaderContentRootInterface $root)
    {
        $form = $this->createForm(BankAccountType::class);
        $form->submit($request->request->all());

        if ($form->isValid()) {
            return $this->getManager()->addBankAccount($root, $form->getData());
        }

        return $form;
    }

    public function getBankAccounts(LeaderContentRootInterface $root)
    {
        $account = $root->getStripeAccount();

        if ($account) {
            return $account->getBankAccounts();
        }

        return [];
    }

    public function deleteBankAccount(LeaderContentRootInterface $root, $id)
    {
        $account = $root->getStripeAccount();
        if ($account) {
            $bankAccount = new BankAccount();
            $bankAccount->setId($id);
            $this->getManager()->deleteBankAccount($account, $bankAccount);
        }
    }
}
