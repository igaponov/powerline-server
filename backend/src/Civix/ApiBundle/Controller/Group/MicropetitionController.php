<?php

namespace Civix\ApiBundle\Controller\Group;

use Civix\ApiBundle\Form\Type\MicropetitionConfigType;
use Civix\CoreBundle\Entity\Group;
use FOS\RestBundle\Controller\Annotations\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/micro-petitions")
 */
class MicropetitionController extends Controller
{
    /**
     * Return micropetitions's config
     *
     * @Route("/config", name="civix_get_micropetition_config")
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
     * @return Group
     */
    public function getConfigAction()
    {
        return $this->getUser();
    }
    
    /**
     * Update micropetitions's config
     *
     * @Route("/config", name="civix_put_micropetition_config")
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
     * @return \Symfony\Component\Form\Form
     */
    public function putConfigAction(Request $request)
    {
        if (!$this->isAvailableChangeConfig()) {
            throw new AccessDeniedException();
        }

        $entityManager = $this->getDoctrine()->getManager();
        $currentGroup = $this->getUser();

        $form = $this->createForm(new MicropetitionConfigType(), $currentGroup, [
            'validation_groups' => ['micropetition-config'],
        ]);
        $form->submit($request);

        if ($form->isValid()) {
            $entityManager->persist($currentGroup);
            $entityManager->flush();

            return $currentGroup;
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
