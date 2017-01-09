<?php

namespace Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\ApiBundle\Form\Type\SocialActivityType;
use Civix\CoreBundle\Entity\SocialActivity;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/social-activities")
 */
class SocialActivityController extends FOSRestController
{
    /**
     * Updates social activity
     *
     * @Route("/{id}")
     * @Method("PUT")
     *
     * @SecureParam("socialActivity", permission="edit")
     *
     * @ParamConverter("socialActivity")
     *
     * @ApiDoc(
     *     authentication=true,
     *     resource=true,
     *     section="Social Activity",
     *     description="Updates social activity",
     *     input="Civix\ApiBundle\Form\Type\SocialActivityType",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\SocialActivity",
     *          "groups" = {"api-activities"}
     *     },
     *     statusCodes={
     *         200="Social Activity",
     *         401="Authorization required",
     *         403="Access Denied",
     *         404="Social Activity is not found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api-activities"})
     *
     * @param Request $request
     * @param SocialActivity $socialActivity
     * 
     * @return SocialActivity|\Symfony\Component\Form\Form
     */
    public function putAction(Request $request, SocialActivity $socialActivity)
    {
        $form = $this->createForm(SocialActivityType::class, $socialActivity);
        $form->submit($request->request->all());
        
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($socialActivity);
            $em->flush();
            
            return $socialActivity;
        }
        
        return $form;
    }
    
    /**
     * Deletes social activity
     * 
     * @Route("/{id}")
     * @Method("DELETE")
     * 
     * @SecureParam("socialActivity", permission="delete")
     *
     * @ParamConverter("socialActivity")
     *
     * @ApiDoc(
     *     authentication=true,
     *     resource=true,
     *     section="Social Activity",
     *     description="Deletes social activity",
     *     statusCodes={
     *         204="Activity is deleted",
     *         401="Authorization required",
     *         403="Access Denied",
     *         404="Social Activity is not found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param SocialActivity $socialActivity
     */
    public function deleteAction(SocialActivity $socialActivity)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($socialActivity);
        $em->flush();
    }
}
