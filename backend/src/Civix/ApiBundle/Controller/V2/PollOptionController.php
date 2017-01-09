<?php
namespace Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\ApiBundle\Form\Type\Poll\OptionType;
use Civix\CoreBundle\Entity\Poll\Option;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/poll-options")
 */
class PollOptionController extends FOSRestController
{
    /**
     * Update poll's option
     *
     * @Route("/{id}")
     * @Method("PUT")
     *
     * @SecureParam("option", permission="content")
     *
     * @ApiDoc(
     *     authentication=true,
     *     resource=true,
     *     section="Polls",
     *     description="Update poll's option",
     *     input="Civix\ApiBundle\Form\Type\Poll\OptionType",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Poll\Option",
     *          "groups" = {"api-poll"}
     *     },
     *     statusCodes={
     *         204="Success",
     *         400="Bad Request",
     *         403="Access Denied",
     *         404="Option Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api-poll"})
     *
     * @param Request $request
     * @param Option $option
     *
     * @return Option|\Symfony\Component\Form\Form
     */
    public function putAction(Request $request, Option $option)
    {
        $form = $this->createForm(OptionType::class, $option);

        $form->submit($request->request->all(), false);
        if ($form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($option);
            $entityManager->flush();

            return $option;
        }

        return $form;
    }

    /**
     * Delete poll's option
     *
     * @Route("/{id}")
     * @Method("DELETE")
     *
     * @SecureParam("option", permission="content")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Polls",
     *     description="Delete poll's option",
     *     statusCodes={
     *         204="Option is deleted",
     *         403="Access Denied",
     *         404="Option Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param Option $option
     */
    public function deleteAction(Option $option)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($option);
        $entityManager->flush();
    }
}