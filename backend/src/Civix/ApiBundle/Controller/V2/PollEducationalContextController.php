<?php
namespace Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\ApiBundle\Form\Type\EducationalContextType;
use Civix\CoreBundle\Entity\Poll\EducationalContext;
use Civix\CoreBundle\Entity\Poll\Question;
use Doctrine\Common\Collections\ArrayCollection;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class for Leader Polls controller
 *
 * @Route("/polls/{poll}/educational-contexts")
 */
class PollEducationalContextController extends FOSRestController
{
    /**
     * List poll's educational contexts.
     *
     * @Route("")
     * @Method("GET")
     *
     * @ParamConverter("question", options={"mapping" = {"poll" = "id"}})
     *
     * @SecureParam("question", permission="view")
     *
     * @ApiDoc(
     *     authentication=true,
     *     resource=true,
     *     section="Polls",
     *     description="List poll's educational contexts.",
     *     output={
     *          "class" = "array<Civix\CoreBundle\Entity\Poll\EducationalContext>",
     *          "groups" = {"api-poll"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\CollectionParser",
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         403="Access Denied",
     *         404="Question Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api-poll"})
     *
     * @param Question $question
     *
     * @return EducationalContext[]|ArrayCollection
     */
    public function getcAction(Question $question)
    {
        return $question->getEducationalContext();
    }

    /**
     * Add educational context
     *
     * @Route("")
     * @Method("POST")
     *
     * @ParamConverter("question", options={"mapping" = {"poll" = "id"}})
     *
     * @SecureParam("question", permission="content")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Polls",
     *     description="Add educational context",
     *     input="Civix\ApiBundle\Form\Type\EducationalContextType",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Poll\EducationalContext",
     *          "groups" = {"api-poll"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         400="Bad Request",
     *         403="Access Denied",
     *         404="Question Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api-poll"})
     *
     * @param Request $request
     * @param Question $question
     *
     * @return EducationalContext|\Symfony\Component\Form\Form
     */
    public function postAction(Request $request, Question $question)
    {
        $context = new EducationalContext();
        $question->addEducationalContext($context);
        $form = $this->createForm(EducationalContextType::class, $context);
        $form->submit($request->request->all());

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($context);
            $em->flush();

            return $context;
        }

        return $form;
    }
}