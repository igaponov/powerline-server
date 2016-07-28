<?php

namespace Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\ApiBundle\Form\Type\Micropetitions\AnswerType;
use Civix\ApiBundle\Form\Type\Micropetitions\PetitionCreateType;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Micropetitions\Answer;
use Civix\CoreBundle\Entity\Micropetitions\Petition;
use Civix\CoreBundle\Service\Micropetitions\PetitionManager;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class MicropetitionController
 * @package Civix\ApiBundle\Controller\V2
 * 
 * @Route("/micro-petitions")
 */
class MicropetitionController extends FOSRestController
{
    /**
     * @var PetitionManager
     * @DI\Inject("civix_core.poll.micropetition_manager")
     */
    private $manager;

    /**
     * List micropetitions
     *
     * @Route("")
     * @Method("GET")
     *
     * @QueryParam(name="tag", requirements=".+", description="Filter by hash tag")
     * @QueryParam(name="page", requirements="\d+", default="1")
     * @QueryParam(name="per_page", requirements="(10|20)", default="20")
     *
     * @ApiDoc(
     *     authentication=true,
     *     resource=true,
     *     section="Micropetitions",
     *     description="List micropetitions",
     *     output="Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination",
     *     filters={
     *          {"name"="start", "dataType"="datetime", "description"="Start date"}
     *     },
     *     statusCodes={
     *         200="Returns micropetitions",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"paginator", "api-petitions-create", "api-petitions-info", "api-petitions-answers"})
     *
     * @param ParamFetcher $params
     *
     * @return \Knp\Component\Pager\Pagination\PaginationInterface
     */
    public function getcAction(ParamFetcher $params)
    {
        $query = $this->getDoctrine()
            ->getRepository('CivixCoreBundle:Micropetitions\Petition')
            ->getFindByQuery($this->getUser(), $params->all());

        return $this->get('knp_paginator')->paginate(
            $query,
            $params->get('page'),
            $params->get('per_page')
        );
    }

    /**
     * Get a single micropetition
     *
     * @Route("/{id}")
     * @Method("GET")
     *
     * @ParamConverter("petition")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Micropetitions",
     *     description="Get a single micropetition",
     *     output={
     *         "class"="Civix\CoreBundle\Entity\Micropetitions\Petition",
     *         "groups"={"api-petitions-create", "api-petitions-info"},
     *         "parsers" = {
     *             "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *         }
     *     },
     *     statusCodes={
     *         200="Returns a micropetition",
     *         404="Micropetition Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api-petitions-create", "api-petitions-info"})
     *
     * @param Petition $petition
     *
     * @return Petition
     */
    public function getAction(Petition $petition)
    {
        return $petition;
    }

    /**
     * Edit a micropetition
     * 
     * @Route("/{id}", requirements={"id"="\d+"})
     * @Method("PUT")
     *
     * @SecureParam("petition", permission="edit")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Micropetitions",
     *     description="Edit a micropetition",
     *     input="Civix\ApiBundle\Form\Type\Micropetitions\PetitionUpdateType",
     *     output={
     *         "class"="Civix\CoreBundle\Entity\Micropetitions\Petition",
     *         "groups"={"api-petitions-create", "api-petitions-info"},
     *         "parsers" = {
     *             "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *         }
     *     },
     *     statusCodes={
     *         200="Returns micropetition's info",
     *         400="Bad Request",
     *         404="Micropetition Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api-petitions-create", "api-petitions-info"})
     *
     * @param Request $request
     * @param Petition $petition
     *
     * @return Response
     */
    public function putAction(Request $request, Petition $petition)
    {
        $form = $this->createForm(new PetitionCreateType(), $petition, ['validation_groups' => 'create']);
        $form->submit($request, false);

        if ($form->isValid()) {
            $this->manager->savePetition($petition);

            return $petition;
        }

        return $form;
    }

    /**
     * Delete a micropetition
     * 
     * @Route("/{id}", requirements={"id"="\d+"})
     * @Method("DELETE")
     *
     * @SecureParam("petition", permission="delete")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Micropetitions",
     *     description="Delete a micropetition",
     *     statusCodes={
     *         204="Returns null",
     *         404="Micropetition Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     * @param Petition $petition
     */
    public function deleteAction(Petition $petition)
    {
        $manager = $this->getDoctrine()->getManager();
        $manager->remove($petition);
        $manager->flush();
    }

    /**
     * Answer to micropetition
     *
     * @Route("/{id}/answer", requirements={"id"="\d+"})
     * @Method("POST")
     *
     * @ParamConverter("petition")
     * @ParamConverter("answer", options={"mapping"={"petition"="petition", "loggedInUser"="user"}}, converter="doctrine.param_converter")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Micropetitions",
     *     description="Answer to micropetition",
     *     input="Civix\ApiBundle\Form\Type\Micropetitions\AnswerType",
     *     output={
     *         "class"="Civix\CoreBundle\Entity\Micropetitions\Answer",
     *         "groups"={"api-leader-answers"},
     *         "parsers" = {
     *             "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *         }
     *     },
     *     statusCodes={
     *         200="Returns micropetition's info",
     *         400="Bad Request",
     *         404="Micropetition Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api-answers-list"})
     *
     * @param Request $request
     * @param Petition $petition
     * @param Answer $answer
     *
     * @return Answer|\Symfony\Component\Form\Form
     */
    public function postAnswerAction(Request $request, Petition $petition, Answer $answer = null)
    {
        if ($answer === null) {
            $answer = new Answer();
            $answer->setUser($this->getUser());
            $answer->setPetition($petition);
        }
        $form = $this->createForm(new AnswerType(), $answer);
        $form->submit($request);

        if ($form->isValid()) {
            $this->manager->signPetition($answer);

            return $answer;
        }

        return $form;
    }

    /**
     * Delete a petition's answer.
     *
     * @Route("/{id}/answer", requirements={"id"="\d+"})
     * @Method("DELETE")
     *
     * @ParamConverter("petition", class="Civix\CoreBundle\Entity\Micropetitions\Petition")
     * @ParamConverter("answer", options={"mapping"={"loggedInUser"="user", "petition"="petition"}}, converter="doctrine.param_converter")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Micropetitions",
     *     description="Delete a petition's answer",
     *     statusCodes={
     *         204="Answer successfully removed",
     *         400="Bad Request",
     *         404="Micropetition or Answer Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param Answer $answer
     */
    public function deleteAnswerAction(Answer $answer)
    {
        $this->manager->unsignPetition($answer);
    }
}
