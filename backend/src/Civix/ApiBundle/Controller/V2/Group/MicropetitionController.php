<?php

namespace Civix\ApiBundle\Controller\V2\Group;

use Civix\ApiBundle\Form\Type\MicropetitionConfigType;
use Civix\CoreBundle\Entity\Group;
use FOS\RestBundle\Controller\Annotations\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/groups/{group}/micro-petitions-config")
 */
class MicropetitionController extends Controller
{
    /**
     * Return micropetition's config
     *
     * @Route("")
     * @Method("GET")
     *
     * @ApiDoc(
     *     section="User Management",
     *     description="Return micropetitions's config",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Group",
     *          "groups" = {"micropetition-config"}
     *     }
     * )
     *
     * @View(serializerGroups={"micropetition-config"})
     *
     * @param Group $group
     *
     * @return Group
     */
    public function getConfigAction(Group $group)
    {
        return $group;
    }

    /**
     * Update micropetition's config
     *
     * @Route("")
     * @Method("PUT")
     *
     * @ApiDoc(
     *     section="User Management",
     *     description="Update micropetitions's config",
     *     input="Civix\ApiBundle\Form\Type\MicropetitionConfigType",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Group",
     *          "groups" = {"micropetition-config"}
     *     }
     * )
     *
     * @View(serializerGroups={"micropetition-config"})
     *
     * @param Request $request
     * @param Group $group
     *
     * @return Group|\Symfony\Component\Form\Form
     */
    public function putConfigAction(Request $request, Group $group)
    {
        if (!$this->isAvailableChangeConfig()) {
            throw new AccessDeniedException();
        }


        $form = $this->createForm(new MicropetitionConfigType(), $group, [
            'validation_groups' => ['micropetition-config'],
        ]);
        $form->submit($request);

        if ($form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($group);
            $entityManager->flush();

            return $group;
        }

        return $form;
    }

    protected function isAvailableChangeConfig()
    {
        $packLimitState = $this->get('civix_core.package_handler')
            ->getPackageStateForMicropetition($this->getUser());

        return $packLimitState->isAllowed();
    }
}
