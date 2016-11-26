<?php

namespace Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\ApiBundle\Form\Type\AnnouncementType;
use Civix\ApiBundle\Form\Type\GroupAnnouncementType;
use Civix\CoreBundle\Entity\Announcement;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Service\AnnouncementManager;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use JMS\DiExtraBundle\Annotation as DI;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Class AnnouncementController
 * @package Civix\ApiBundle\Controller\V2
 *
 * @Route("/announcements")
 */
class AnnouncementController extends FOSRestController
{
    /**
     * @var AnnouncementManager
     * @DI\Inject("civix_core.announcement_manager")
     */
    private $manager;

    /**
     * Return a user's list of announcements
     *
     * @Route("")
     * @Method("GET")
     *
     * @QueryParam(name="page", requirements="\d+", default="1")
     * @QueryParam(name="per_page", requirements="(10|20)", default="20")
     *
     * @ApiDoc(
     *     authentication=true,
     *     resource=true,
     *     section="Announcements",
     *     description="Return a user's list of announcements",
     *     output = {
     *          "class" = "array<Civix\CoreBundle\Entity\Announcement> as paginator",
     *          "groups" = {"api", "api-activities"},
     *          "parsers" = {
     *              "Civix\ApiBundle\Parser\PaginatorParser"
     *          }
     *     },
     *     filters={
     *         {
     *             "name" = "start",
     *             "dataType" = "datetime",
     *             "description" = "Start date",
     *             "default" = "-1 day"
     *         }
     *     },
     *     statusCodes={
     *         200="Returns announcements",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"paginator", "api", "api-activities"})
     *
     * @param ParamFetcher $params
     * @return \Knp\Component\Pager\Pagination\PaginationInterface
     */
    public function getcAction(ParamFetcher $params)
    {
        $param = new QueryParam();
        $param->name = 'start';
        $param->requirements = new Assert\DateTime();
        $param->default = '-1 day';
        $params->addParam($param);

        $start = new \DateTime($params->get('start'));

        $query = $this->getDoctrine()->getRepository(Announcement::class)
            ->getByUserQuery($this->getUser(), $start);

        return $this->get('knp_paginator')->paginate(
            $query,
            $params->get('page'),
            $params->get('per_page')
        );
    }

    /**
     * Returns an announcement
     *
     * @Route("/{id}")
     * @Method("GET")
     *
     * @SecureParam("announcement", permission="view")
     *
     * @ApiDoc(
     *     authentication=true,
     *     resource=true,
     *     section="Announcements",
     *     description="Returns an announcement",
     *     output = {
     *          "class" = "Civix\CoreBundle\Entity\Announcement",
     *          "groups" = {"api"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         200="Returns an announcement",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api"})
     *
     * @param Announcement $announcement
     *
     * @return Announcement|\Symfony\Component\Form\Form
     */
    public function getAction(Announcement $announcement)
    {
        return $announcement;
    }

    /**
     * Edits an unpublished announcement
     *
     * @Route("/{id}")
     * @Method("PUT")
     *
     * @SecureParam("announcement", permission="manage")
     *
     * @ApiDoc(
     *     authentication=true,
     *     resource=true,
     *     section="Announcements",
     *     description="Edits an announcement",
     *     input="Civix\ApiBundle\Form\Type\AnnouncementType",
     *     output = {
     *          "class" = "Civix\CoreBundle\Entity\Announcement",
     *          "groups" = {"api"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         200="Returns an announcement",
     *         400="Bad Request",
     *         403="Access Denied or Announcement is published",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api"})
     *
     * @param Request $request
     * @param Announcement $announcement
     *
     * @return Announcement|\Symfony\Component\Form\Form
     */
    public function putAction(Request $request, Announcement $announcement)
    {
        $leader = $announcement->getRoot();
        if ($leader instanceof Group) {
            $type = new GroupAnnouncementType($leader);
        } else {
            $type = new AnnouncementType();
        }
        $form = $this->createForm($type, $announcement, [
            'validation_groups' => ['Default', 'update']
        ]);
        $form->submit($request, false);

        if ($form->isValid()) {
            $manager = $this->getDoctrine()->getManager();
            $manager->persist($announcement);
            $manager->flush();

            return $announcement;
        }

        return $form;
    }

    /**
     * Publishes an announcement
     *
     * @Route("/{id}")
     * @Method("PATCH")
     *
     * @SecureParam("announcement", permission="manage")
     * @SecureParam("announcement", permission="publish")
     *
     * @ApiDoc(
     *     authentication=true,
     *     resource=true,
     *     section="Announcements",
     *     description="Publishes an announcement",
     *     input="Civix\ApiBundle\Form\Type\AnnouncementType",
     *     output = {
     *          "class" = "Civix\CoreBundle\Entity\Announcement",
     *          "groups" = {"api"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         200="Returns an announcement",
     *         400="Bad Request",
     *         403="Access Denied, Announcement is published or Announcement's limit has been exceeded",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api"})
     *
     * @param Announcement $announcement
     *
     * @return Announcement|\Symfony\Component\Validator\ConstraintViolationList|ConstraintViolationListInterface
     */
    public function patchAction(Announcement $announcement)
    {
        $violations = $this->get('validator')->validate($announcement, ['publish']);
        if (!$violations->count()) {
            return $this->manager->publish($announcement);
        }

        return $violations;
    }

    /**
     * Deletes an unpublished announcement
     *
     * @Route("/{id}")
     * @Method("DELETE")
     *
     * @SecureParam("announcement", permission="manage")
     *
     * @ApiDoc(
     *     authentication=true,
     *     resource=true,
     *     section="Announcements",
     *     description="Deletes an announcement",
     *     input="Civix\ApiBundle\Form\Type\AnnouncementType",
     *     statusCodes={
     *         204="Deleted",
     *         400="Bad Request",
     *         403="Access Denied or Announcement is published",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param Announcement $announcement
     *
     * @return \Symfony\Component\Validator\ConstraintViolationList|ConstraintViolationListInterface
     */
    public function deleteAction(Announcement $announcement)
    {
        $violations = $this->get('validator')->validate($announcement, ['publish']);
        if (!$violations->count()) {
            $manager = $this->getDoctrine()->getManager();
            $manager->remove($announcement);
            $manager->flush();

            return null;
        }

        return $violations;
    }
}
