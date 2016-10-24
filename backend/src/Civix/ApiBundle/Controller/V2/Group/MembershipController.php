<?php

namespace Civix\ApiBundle\Controller\V2\Group;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\ApiBundle\Form\Type\Group\MembershipType;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Service\Group\GroupManager;
use Civix\CoreBundle\Service\Subscription\SubscriptionManager;
use FOS\RestBundle\Controller\Annotations\View;
use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @Route("/groups/{group}/membership")
 */
class MembershipController extends Controller
{
    /**
     * @var GroupManager
     * @DI\Inject("civix_core.group_manager")
     */
    private $manager;

    /**
     * @var SubscriptionManager
     * @DI\Inject("civix_core.subscription_manager")
     */
    private $subscriptionManager;

    /**
     * Return group's membership control
     *
     * @Route("")
     * @Method("GET")
     *
     * @SecureParam("group", permission="view")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Return group's membership control",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Group",
     *          "groups" = {"membership-control"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         403="Access Denied",
     *         404="Group Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"membership-control"})
     *
     * @param Group $group
     *
     * @return Group
     */
    public function getAction(Group $group)
    {
        return $group;
    }

    /**
     * Update micropetitions's config
     *
     * @Route("")
     * @Method("PUT")
     *
     * @SecureParam("group", permission="membership")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Groups",
     *     description="Update group's membership control",
     *     input="Civix\ApiBundle\Form\Type\Group\MembershipType",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Group",
     *          "groups" = {"membership-control"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         400="Bad Request",
     *         403="Access Denied",
     *         404="Group Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"membership-control"})
     *
     * @param Request $request
     * @param Group $group
     *
     * @return \Symfony\Component\Form\Form|\Civix\CoreBundle\Entity\Group
     */
    public function putAction(Request $request, Group $group)
    {
        if (!$this->subscriptionManager->getSubscription($group)->isNotFree()) {
            throw new BadRequestHttpException('You must have a Silver subscription or above to change membership controls. Upgrade today!');
        }
        $form = $this->createForm(new MembershipType(), $group);
        
        $form->submit($request);
        
        if ($form->isValid()) {
            return $this->manager->changeMembershipControl($group);
        }

        return $form;
    }
}
