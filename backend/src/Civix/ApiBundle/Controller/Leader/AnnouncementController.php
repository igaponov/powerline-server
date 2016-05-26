<?php

namespace Civix\ApiBundle\Controller\Leader;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\ApiBundle\Form\Type\AnnouncementType;
use Civix\CoreBundle\Entity\Announcement;
use Civix\CoreBundle\Entity\UserInterface;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class AnnouncementController
 * @package Civix\ApiBundle\Controller\V2
 *
 * @Route("/announcements")
 */
class AnnouncementController extends FOSRestController
{
    /**
     * Adds an user's announcement
     *
     * @Route("")
     * @Method("POST")
     *
     * @ApiDoc(
     *     authentication=true,
     *     resource=true,
     *     section="Announcement",
     *     description="Adds an user's announcement",
     *     input="Civix\ApiBundle\Form\Type\AnnouncementType",
     *     output = {
     *          "class" = "Civix\CoreBundle\Entity\Announcement",
     *          "groups" = {"api"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         200="Returns a new announcement",
     *         400="Bad Request",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api"})
     *
     * @param Request $request
     *
     * @return Announcement|\Symfony\Component\Form\Form
     */
    public function postAction(Request $request)
    {
        /** @var UserInterface $user */
        $user = $this->getUser();
        $class = $this->getEntityClass($user->getType());
        /** @var Announcement $announcement */
        $announcement = new $class;
        $form = $this->createForm(new AnnouncementType(), $announcement);
        $form->submit($request);

        if ($form->isValid()) {
            $announcement->setUser($user);
            $manager = $this->getDoctrine()->getManager();
            $manager->persist($announcement);
            $manager->flush();

            return $announcement;
        }

        return $form;
    }

    /**
     * Returns an announcement
     *
     * @Route("/{id}")
     * @Method("GET")
     *
     * @ParamConverter("announcement")
     * 
     * @SecureParam("announcement", permission="view")
     *
     * @ApiDoc(
     *     authentication=true,
     *     resource=true,
     *     section="Announcement",
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
     * @ParamConverter("announcement")
     * 
     * @SecureParam("announcement", permission="edit")
     *
     * @ApiDoc(
     *     authentication=true,
     *     resource=true,
     *     section="Announcement",
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
        $form = $this->createForm(new AnnouncementType(), $announcement);
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
     * @ParamConverter("announcement")
     *
     * @SecureParam("announcement", permission="publish")
     * 
     * @ApiDoc(
     *     authentication=true,
     *     resource=true,
     *     section="Announcement",
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
     * @return Announcement|\Symfony\Component\Form\Form
     */
    public function patchAction(Announcement $announcement)
    {
        $announcement->setPublishedAt(new \DateTime());
        $manager = $this->getDoctrine()->getManager();
        $manager->persist($announcement);
        $manager->flush();

        return $announcement;
    }

    /**
     * Deletes an unpublished announcement
     *
     * @Route("/{id}")
     * @Method("DELETE")
     *
     * @ParamConverter("announcement")
     *
     * @SecureParam("announcement", permission="delete")
     * 
     * @ApiDoc(
     *     authentication=true,
     *     resource=true,
     *     section="Announcement",
     *     description="Deletes an announcement",
     *     input="Civix\ApiBundle\Form\Type\AnnouncementType",
     *     statusCodes={
     *         204="Deleted",
     *         403="Access Denied or Announcement is published",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param Announcement $announcement
     */
    public function deleteAction(Announcement $announcement)
    {
        $manager = $this->getDoctrine()->getManager();
        $manager->remove($announcement);
        $manager->flush();
    }

    protected function getEntityClass($type)
    {
        return 'Civix\\CoreBundle\\Entity\\Announcement\\'.ucfirst($type).'Announcement';
    }
}
