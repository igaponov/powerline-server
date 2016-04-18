<?php

namespace Civix\FrontBundle\Controller;

use Civix\CoreBundle\Entity\Group;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Civix\FrontBundle\Form\Type\Settings\Permissions;

class PermissionSettingsController extends Controller
{
    /**
     * @Route("/")
     * @Template("CivixFrontBundle:PermissionSettings:index.html.twig")
     */
    public function indexAction(Request $request)
    {
        /** @var Group $user */
        $user = $this->getUser();
        $entityManager = $this->getDoctrine()->getManager();
        $permissionForm = $this->createForm(new Permissions(), $user);
        $previousPermissions = $user->getRequiredPermissions();

        if ('POST' === $request->getMethod() && $permissionForm->submit($request)->isValid()) {
            $shouldNotify = $this->get('civix_core.group_manager')
                ->isMorePermissions($previousPermissions, $user->getRequiredPermissions());
            $user->setPermissionsChangedAt(new \DateTime());
            $entityManager->persist($user);
            $entityManager->flush($user);
            if ($shouldNotify) {
                $this->get('civix_core.social_activity_manager')->noticeGroupsPermissionsChanged($user);
            }
        }

        return [
            'form' => $permissionForm->createView(),
        ];
    }
}
