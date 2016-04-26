<?php
namespace Civix\ApiBundle\Controller\Leader;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\CoreBundle\Entity\Poll\Option;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\ApiBundle\Form\Type\Poll\OptionType;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/options")
 */
class PollOptionController extends FOSRestController
{
    /**
     * Update poll's option
     *
     * @Route("/{id}", name="civix_put_poll_option")
     * @Method("PUT")
     * @SecureParam("option", permission="edit")
     *
     * @ParamConverter("option")
     *
     * @ApiDoc(
     *     section="Leader Content",
     *     description="Update poll's option",
     *     input="Civix\ApiBundle\Form\Type\Poll\OptionType",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Poll\Option",
     *          "groups" = {"api-poll"}
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
        $form = $this->createForm(new OptionType(), $option);

        $form->submit($request, false);
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
     * @Route("/{id}", name="civix_delete_poll_option")
     * @Method("DELETE")
     * @SecureParam("option", permission="delete")
     *
     * @ParamConverter("option")
     *
     * @ApiDoc(
     *     section="Leader Content",
     *     description="Delete poll's option",
     *     statusCodes={
     *         204="Option is deleted",
     *         400="Bad Request",
     *         401="Authorization required",
     *         404="Option not found",
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