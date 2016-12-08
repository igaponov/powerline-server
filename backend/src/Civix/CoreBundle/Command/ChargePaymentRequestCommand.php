<?php

namespace Civix\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Civix\CoreBundle\Entity\Poll\Question\PaymentRequest;
use Civix\CoreBundle\Entity\Poll\Answer;
use Civix\CoreBundle\Entity\Stripe\Charge;

class ChargePaymentRequestCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('payment-request:charge')
            ->setDescription('Charging crowdfunding payment requests')
            ->addArgument(
                'id',
                InputArgument::REQUIRED,
                'Payment request id'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var PaymentRequest $paymentRequest */
        $paymentRequest = $this->getContainer()->get('doctrine')->getRepository(PaymentRequest::class)
            ->find($input->getArgument('id'));

        if (!$paymentRequest || !$paymentRequest->getIsCrowdfunding()) {
            $output->writeln('<error>Cannot find payment request.</error>');
            return;
        }

        if (!$paymentRequest->isCrowdfundingDeadline()) {
            $output->writeln('<error>Deadline is not reached.</error>');
            return;
        }

        /** @var \Doctrine\ORM\EntityRepository $chargeRepository */
        $chargeRepository = $this->getContainer()->get('doctrine')->getRepository(Charge::class);

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();

        if ($paymentRequest->getCrowdfundingGoalAmount() > $paymentRequest->getCrowdfundingPledgedAmount()) {
            $paymentRequest->setIsCrowdfundingCompleted(true);
            $em->flush($paymentRequest);

            return;
        }

        /** @var Answer $answer */
        foreach ($paymentRequest->getAnswers() as $answer) {
            $customer = $answer->getUser()->getStripeCustomer();

            if (!$customer) {
                continue;
            }

            $charge = $chargeRepository->findOneBy([
                    'question' => $answer->getQuestion(),
                    'fromCustomer' => $customer,
                ])
            ;

            if ($answer->getOption()->getPaymentAmount() && !$charge) {
                try {
                    $pollManager = $this->getContainer()->get('civix_core.poll_manager');
                    $pollManager->chargeToPaymentRequest($paymentRequest, $answer);
                    $em->flush();
                    $output->writeln("<comment>User {$answer->getUser()->getId()} has charged</comment>");
                } catch (\Exception $e) {
                    $output->writeln("<error>{$e->getMessage()}</error>");
                }
            } else {
                $output->writeln("<comment> Already paid: {$answer->getUser()->getId()} </comment>");
            }
        }

        $paymentRequest->setIsCrowdfundingCompleted(true);
        $em->persist($paymentRequest);
        $em->flush();

        $output->writeln('Charged.');
    }
}
