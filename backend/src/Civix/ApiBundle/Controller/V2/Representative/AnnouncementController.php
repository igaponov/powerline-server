<?php

namespace Civix\ApiBundle\Controller\V2\Representative;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\ApiBundle\Form\Type\AnnouncementType;
use Civix\CoreBundle\Entity\Announcement;
use Civix\CoreBundle\Entity\Representative;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Route("/representatives/{representative}/announcements")
 */
class AnnouncementController extends FOSRestController
{
    /**
     * Adds an user's announcement
     *
     * @Route("")
     * @Method("POST")
     *
     * @SecureParam("representative", permission="edit")
     *
     * @ApiDoc(
     *     authentication=true,
     *     resource=true,
     *     section="Announcements",
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
     * @param Representative $representative
     *
     * @return Announcement|\Symfony\Component\Form\Form
     */
    public function postAction(Request $request, Representative $representative)
    {
        /** @var Announcement $announcement */
        $announcement = new Announcement\RepresentativeAnnouncement();
        $form = $this->createForm(new AnnouncementType(), $announcement);
        $form->submit($request);

        if ($form->isValid()) {
            $announcement->setRoot($representative);
            $manager = $this->getDoctrine()->getManager();
            $manager->persist($announcement);
            $manager->flush();

            return $announcement;
        }

        return $form;
    }
}
