<?php
namespace Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\CoreBundle\Entity\Poll\EducationalContext;
use FOS\RestBundle\Controller\FOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route("/educational-contexts")
 */
class EducationalContextController extends FOSRestController
{
    /**
     * Delete poll's educational context
     *
     * @Route("/{id}")
     * @Method("DELETE")
     *
     * @SecureParam("context", permission="content")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Polls",
     *     description="Delete poll's educational context",
     *     statusCodes={
     *         204="Educational Context is deleted",
     *         403="Access Denied",
     *         404="Educational Context Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param EducationalContext $context
     */
    public function deleteAction(EducationalContext $context)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($context);
        $entityManager->flush();
    }
}