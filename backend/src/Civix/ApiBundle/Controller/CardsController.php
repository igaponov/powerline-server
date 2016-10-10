<?php

namespace Civix\ApiBundle\Controller;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Civix\CoreBundle\Entity\Stripe\Customer;

/**
 * @Route("/cards")
 */
class CardsController extends BaseController
{
    /**
     * Deprecated, use `POST /api/v2/cards` instead
     *
     * @Route("/")
     * @Method("POST")
     *
     * @ApiDoc(
     *     section="Users",
     *     deprecated=true
     * )
     */
    public function add()
    {
        return $this->forward('CivixApiBundle:V2/Card:postCard');
    }

    /**
     * Deprecated, use `GET /api/v2/cards` instead
     *
     * @Route("/")
     * @Method("GET")
     *
     * @ApiDoc(
     *     section="Users",
     *     deprecated=true
     * )
     */
    public function listCards()
    {
        $cards = [];
        /* @var Customer $customer */
        $customer = $this->getDoctrine()
            ->getRepository(Customer::getEntityClassByUser($this->getUser()))
            ->findOneBy(['user' => $this->getUser()]);
        if ($customer) {
            $cards = $customer->getCards();
        }

        return $this->createJSONResponse($this->jmsSerialization($cards, ['api']));
    }

    /**
     * Deprecated, use `DELETE /api/v2/cards/{id}` instead
     *
     * @Route("/{id}")
     * @Method("DELETE")
     *
     * @ApiDoc(
     *     section="Users",
     *     deprecated=true
     * )
     *
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function removeCard($id)
    {
        return $this->forward('CivixApiBundle:V2/Card:deleteCard', ['id' => $id]);
    }
}
