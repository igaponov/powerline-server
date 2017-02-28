<?php

namespace Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\ApiBundle\Form\Type\UserPetitionCreateType;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Entity\UserPetition\Signature;
use Civix\CoreBundle\Service\UserPetitionManager;
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

/**
 * @Route("/user-petitions")
 */
class UserPetitionController extends FOSRestController
{
    /**
     * @var UserPetitionManager
     * @DI\Inject("civix_core.user_petition_manager")
     */
    private $manager;

    /**
     * List petitions
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
     *     section="User Petitions",
     *     description="List petitions",
     *     output="Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination",
     *     filters={
     *          {"name"="start", "dataType"="datetime", "description"="Start date"}
     *     },
     *     statusCodes={
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"Default", "api-petitions-answers"})
     *
     * @param ParamFetcher $params
     *
     * @return \Knp\Component\Pager\Pagination\PaginationInterface
     */
    public function getcAction(ParamFetcher $params)
    {
        $query = $this->getDoctrine()
            ->getRepository(UserPetition::class)
            ->getFindByUserQuery($this->getUser(), $params->all());

        return $this->get('knp_paginator')->paginate(
            $query,
            $params->get('page'),
            $params->get('per_page')
        );
    }

    /**
     * Get a single petition
     *
     * @Route("/{id}")
     * @Method("GET")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="User Petitions",
     *     description="Get a single petition",
     *     output={
     *         "class"="Civix\CoreBundle\Entity\UserPetition",
     *         "groups"={"Default"},
     *         "parsers" = {
     *             "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *         }
     *     },
     *     statusCodes={
     *         404="Petition Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param UserPetition $petition
     *
     * @return UserPetition
     */
    public function getAction(UserPetition $petition)
    {
        return $petition;
    }

    /**
     * Edit a petition
     *
     * @Route("/{id}", requirements={"id"="\d+"})
     * @Method("PUT")
     *
     * @SecureParam("petition", permission="edit")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="User Petitions",
     *     description="Edit a petition",
     *     input="Civix\ApiBundle\Form\Type\UserPetitionUpdateType",
     *     output={
     *         "class"="Civix\CoreBundle\Entity\UserPetition",
     *         "groups"={"Default"},
     *         "parsers" = {
     *             "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *         }
     *     },
     *     statusCodes={
     *         400="Bad Request",
     *         404="Petition Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param Request $request
     * @param UserPetition $petition
     *
     * @return UserPetition|\Symfony\Component\Form\Form
     */
    public function putAction(Request $request, UserPetition $petition)
    {
        $form = $this->createForm(UserPetitionCreateType::class, $petition, ['validation_groups' => 'create']);
        $form->submit($request->request->all(), false);

        if ($form->isValid()) {
            $this->manager->savePetition($petition);

            return $petition;
        }

        return $form;
    }

    /**
     * Boost a petition
     *
     * @Route("/{id}", requirements={"id"="\d+"})
     * @Method("PATCH")
     *
     * @SecureParam("petition", permission="edit")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="User Petitions",
     *     description="Boost a petition",
     *     output={
     *         "class"="Civix\CoreBundle\Entity\UserPetition",
     *         "groups"={"Default"},
     *         "parsers" = {
     *             "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *         }
     *     },
     *     statusCodes={
     *         400="Bad Request",
     *         403="Access Denied",
     *         404="Petition Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param UserPetition $petition
     *
     * @return UserPetition
     */
    public function patchAction(UserPetition $petition)
    {
        if (!$petition->isBoosted()) {
            $this->manager->boostPetition($petition);
        }

        return $petition;
    }

    /**
     * Delete a petition
     * 
     * @Route("/{id}", requirements={"id"="\d+"})
     * @Method("DELETE")
     *
     * @SecureParam("petition", permission="delete")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="User Petitions",
     *     description="Delete a petition",
     *     statusCodes={
     *         204="Success",
     *         404="Petition Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     * @param UserPetition $petition
     */
    public function deleteAction(UserPetition $petition)
    {
        $manager = $this->getDoctrine()->getManager();
        $manager->remove($petition);
        $manager->flush();
    }

    /**
     * Sign a petition
     *
     * @Route("/{id}/sign", requirements={"id"="\d+"})
     * @Method("POST")
     *
     * @ParamConverter("petition")
     * @ParamConverter("signature", options={"mapping"={"petition"="petition", "loggedInUser"="user"}}, converter="doctrine.param_converter")
     *
     * @SecureParam("petition", permission="sign")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="User Petitions",
     *     description="Answer to petition",
     *     output={
     *         "class"="Civix\CoreBundle\Entity\UserPetition\Signature",
     *         "groups"={"Default"},
     *         "parsers" = {
     *             "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *         }
     *     },
     *     statusCodes={
     *         400="Bad Request",
     *         404="Petition Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param UserPetition $petition
     * @param Signature $signature
     *
     * @return Signature|\Symfony\Component\Form\Form
     */
    public function postSignatureAction(UserPetition $petition, Signature $signature = null)
    {
        if ($signature !== null) {
            return null;
        }

        return $this->manager->signPetition($petition, $this->getUser());
    }

    /**
     * Delete a petition's signature.
     *
     * @Route("/{id}/sign", requirements={"id"="\d+"})
     * @Method("DELETE")
     *
     * @ParamConverter("petition", class="Civix\CoreBundle\Entity\UserPetition")
     * @ParamConverter("signature", options={"mapping"={"loggedInUser"="user", "petition"="petition"}}, converter="doctrine.param_converter")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="User Petitions",
     *     description="Delete a petition's signature",
     *     statusCodes={
     *         204="Success",
     *         400="Bad Request",
     *         404="Petition or Signature Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param Signature $signature
     */
    public function deleteSignatureAction(Signature $signature)
    {
        $this->manager->unsignPetition($signature);
    }

    /**
     * Mark a petition as a spam
     *
     * @Route("/{id}/spam", requirements={"id"="\d+"})
     * @Method("POST")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="User Petitions",
     *     description="Mark a petition as a spam",
     *     statusCodes={
     *         400="Bad Request",
     *         404="Petition Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param UserPetition $petition
     */
    public function postSpamAction(UserPetition $petition)
    {
        $petition->markAsSpam($this->getUser());
        $this->manager->savePetition($petition);
    }

    /**
     * Mark a petition as not a spam.
     *
     * @Route("/{id}/spam", requirements={"id"="\d+"})
     * @Method("DELETE")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="User Petitions",
     *     description="Mark a petition as not a spam",
     *     statusCodes={
     *         204="Success",
     *         400="Bad Request",
     *         404="Petition or Vote Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param UserPetition $petition
     */
    public function deleteSpamAction(UserPetition $petition)
    {
        $petition->markAsNotSpam($this->getUser());
        $this->manager->savePetition($petition);
    }
}
