<?php

namespace Civix\ApiBundle\Controller\Group;

use Civix\ApiBundle\Form\Type\Group\MembershipType;
use Civix\CoreBundle\Entity\Group;
use FOS\RestBundle\Controller\Annotations\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/membership")
 */
class MembershipController extends Controller
{
    /**
     * Return group's membership control
     *
     * @Route("", name="civix_get_group_membership")
     * @Method("GET")
     *
     * @ApiDoc(
     *     section="User Management",
     *     description="Return group's membership control",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Group",
     *          "groups" = {"membership-control"}
     *     }
     * )
     *
     * @View(serializerGroups={"membership-control"})
     *
     * @return Group
     */
    public function getAction()
    {
        return $this->getUser();
    }
    
    /**
     * Update micropetitions's config
     *
     * @Route("", name="civix_put_group_membership")
     * @Method("PUT")
     *
     * @ApiDoc(
     *     section="User Management",
     *     description="Update group's membership control",
     *     input="Civix\ApiBundle\Form\Type\Group\MembershipType",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Group",
     *          "groups" = {"membership-control"}
     *     }
     * )
     *
     * @View(serializerGroups={"membership-control"})
     *
     * @param Request $request
     * @return \Symfony\Component\Form\Form
     */
    public function putAction(Request $request)
    {
        $group = $this->getUser();
        $form = $this->createForm(new MembershipType(), $group);
        
        $form->submit($request);
        
        if ($form->isValid()) {
            return $this->get('civix_core.group_manager')
                ->changeMembershipControl($group);
        }

        return $form;
    }
}
