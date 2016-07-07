<?php

namespace Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Form\Type\ActivitiesType;
use Civix\CoreBundle\Entity\Activity;
use Civix\CoreBundle\Entity\ActivityRead;
use Civix\CoreBundle\Entity\UserFollow;
use Doctrine\Common\Collections\ArrayCollection;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use Knp\Component\Pager\Event\AfterEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * @Route("/activities")
 */
class ActivityController extends FOSRestController
{
    /**
     * Return a user's list of activities
     *
     * @Route("")
     * @Method("GET")
     *
     * @QueryParam(name="following", requirements="\d+", description="Following user id")
     * @QueryParam(name="page", requirements="\d+", default="1")
     * @QueryParam(name="per_page", requirements="(10|20)", default="20")
     *
     * @ApiDoc(
     *     authentication = true,
     *     resource=true,
     *     section="Activity",
     *     output = {
     *          "class" = "Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination",
     *          "groups" = {"paginator", "api-activities"}
     *     },
     *     description="Return a user's list of activities",
     *     statusCodes={
     *          200="Returned when successful",
     *          405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"paginator", "api-activities"})
     *
     * @param ParamFetcher $params
     * @return Response
     */
    public function getcAction(ParamFetcher $params)
    {
        $start = new \DateTime('-30 days');

        $user = $this->getUser();
        if ($followingId = $params->get('following')) {
            // get user follow status
            $userFollow = $this->getDoctrine()
                ->getRepository('CivixCoreBundle:UserFollow')->findOneBy([
                    'user' => $followingId,
                    'follower' => $user,
                    'status' => UserFollow::STATUS_ACTIVE,
                ]);
            if (!$userFollow) {
                $query = [];
            } else {
                $query = $this->getDoctrine()
                    ->getRepository('CivixCoreBundle:Activity')
                    ->getActivitiesByFollowingQuery($userFollow->getUser(), $start);
            }
        } else {
            $query = $this->getDoctrine()->getRepository('CivixCoreBundle:Activity')
                ->getActivitiesByUserQuery($user, $start);
        }

        $paginator = $this->get('knp_paginator');
        $paginator->connect('knp_pager.after', function (AfterEvent $event) use ($user) {
            $filter = function (ActivityRead $activityRead) use ($user) {
                return $activityRead->getUser()->getId() == $user->getId();
            };
            foreach ($event->getPaginationView() as $activity) {
                /** @var Activity $activity */
                if ($activity->getActivityRead()->filter($filter)->count()) {
                    $activity->setRead(true);
                }
            }
        });

        return $paginator->paginate(
            $query,
            $params->get('page'),
            $params->get('per_page'),
            ['distinct' => false]
        );
    }

    /**
     * Bulk update activities
     *
     * @Route("")
     * @Method("PATCH")
     *
     * @ApiDoc(
     *     authentication = true,
     *     resource=true,
     *     section="Activity",
     *     description="Bulk update activities",
     *     input = "Civix\ApiBundle\Form\Type\ActivitiesType",
     *     output = {
     *          "class" = "array<Civix\CoreBundle\Entity\Activity>",
     *          "groups" = {"api-activities"}
     *     },
     *     statusCodes={
     *          200="Returned when successful",
     *          405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api-activities"})
     *
     * @param Request $request
     * @return \Symfony\Component\Form\Form|Response
     */
    public function patchAction(Request $request)
    {
        $form = $this->createForm(new ActivitiesType());
        $form->submit($request);
        
        if ($form->isValid()) {
            return $this->get('civix_core.service.activity_manager')
                ->bulkUpdate(new ArrayCollection($form->getData()['activities']), $this->getUser());
        }

        return $form;
    }
}
