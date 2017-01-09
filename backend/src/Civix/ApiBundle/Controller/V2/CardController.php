<?php

namespace Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Controller\BaseController;
use Civix\ApiBundle\Form\Type\CardType;
use Civix\CoreBundle\Entity\Stripe\Card;
use Civix\CoreBundle\Entity\Stripe\Customer;
use Civix\CoreBundle\Service\PaymentManager;
use FOS\RestBundle\Controller\Annotations\View;
use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/cards")
 */
class CardController extends BaseController
{
    /**
     * @var PaymentManager
     * @DI\Inject("civix_core.payment_manager")
     */
    private $manager;

    /**
     * @Route("")
     * @Method("POST")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Users",
     *     description="Add user's card",
     *     input="Civix\ApiBundle\Form\Type\CardType",
     *     statusCodes={
     *         400="Bad Request",
     *         403="Access Denied",
     *         405="Method Not Allowed"
     *     },
     *     responseMap={
     *          201={
     *              "class" = "Civix\CoreBundle\Entity\Stripe\Customer",
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
     *
     * @return Customer|\Symfony\Component\Form\Form
     */
    public function postCardAction(Request $request)
    {
        $form = $this->createForm(CardType::class);
        $form->submit($request->request->all());

        if ($form->isValid()) {
            return $this->manager->addCard($this->getUser(), $form->getData());
        }

        return $form;
    }

    /**
     * @Route("")
     * @Method("GET")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Users",
     *     description="Get user's cards",
     *     output={
     *          "class" = "array<Civix\CoreBundle\Entity\Stripe\Card>",
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\CollectionParser",
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         403="Access Denied",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @return array|mixed
     */
    public function getCardsAction()
    {
        /* @var Customer $customer */
        $customer = $this->getUser()->getStripeCustomer();

        if ($customer) {
            return $customer->getCards();
        }

        return [];
    }

    /**
     * @Route("/{id}", requirements={"id" = ".+"})
     * @Method("DELETE")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Users",
     *     description="Delete user's card",
     *     statusCodes={
     *         204="Success",
     *         403="Access Denied",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param string $id
     */
    public function deleteCardAction($id)
    {
        /* @var Customer $customer */
        $customer = $this->getUser()->getStripeCustomer();
        if ($customer) {
            $card = new Card();
            $card->setId($id);
            $this->manager->deleteCard($customer, $card);
        }
    }
}
