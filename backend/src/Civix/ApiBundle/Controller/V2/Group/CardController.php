<?php

namespace Civix\ApiBundle\Controller\V2\Group;

use Civix\ApiBundle\Controller\BaseController;
use Civix\ApiBundle\Form\Type\CardType;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Stripe\Card;
use Civix\CoreBundle\Entity\Stripe\Customer;
use Civix\CoreBundle\Entity\Stripe\CustomerGroup;
use Civix\CoreBundle\Service\StripeAccountManager;
use FOS\RestBundle\Controller\Annotations\View;
use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/groups/{group}/cards")
 */
class CardController extends BaseController
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
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Add group's card",
     *     input="Civix\ApiBundle\Form\Type\CardType",
     *     statusCodes={
     *         400="Bad Request",
     *         403="Access Denied",
     *         404="Group Not Found",
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
     * @param Group $group
     *
     * @return Customer|\Symfony\Component\Form\Form
     */
    public function postCardAction(Request $request, Group $group)
    {
        $form = $this->createForm(new CardType());
        $form->submit($request);

        if ($form->isValid()) {
            return $this->manager->addGroupCard($group, $form->getData());
        }

        return $form;
    }

    /**
     * @Route("")
     * @Method("GET")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Get group's cards",
     *     output={
     *          "class" = "array<Civix\CoreBundle\Entity\Stripe\Card>",
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
     * @param Group $group
     *
     * @return array|mixed
     */
    public function getCardsAction(Group $group)
    {
        /* @var Customer $customer */
        $customer = $this->getDoctrine()
            ->getRepository(CustomerGroup::class)
            ->findOneBy(['user' => $group]);

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
     *     section="Groups",
     *     description="Delete group's card",
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
    public function deleteCardAction(Group $group, $id)
    {
        /* @var Customer $customer */
        $customer = $this->getDoctrine()
            ->getRepository(CustomerGroup::class)
            ->findOneBy(['user' => $group]);
        if ($customer) {
            $card = new Card();
            $card->setId($id);
            $this->manager->deleteCard($customer, $card);
        }
    }
}
