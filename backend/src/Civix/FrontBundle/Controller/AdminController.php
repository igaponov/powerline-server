<?php

namespace Civix\FrontBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Superuser controller.
 * @Route("/admin")
 */
class AdminController extends Controller
{
    /**
     * @Route("", name="civix_front_superuser")
     * @Method({"GET"})
     */
    public function indexAction()
    {
        return $this->redirectToRoute('civix_front_representative_approvals');
    }

    /**
     * @Route("/login", name="civix_front_superuser_login")
     * @Method({"GET"})
     * @Template("CivixFrontBundle:Admin:login.html.twig")
     */
    public function loginAction()
    {
        $utils = $this->get('security.authentication_utils');

        return [
            'last_username' => $utils->getLastUsername(),
            'error' => $utils->getLastAuthenticationError(),
        ];
    }
}
