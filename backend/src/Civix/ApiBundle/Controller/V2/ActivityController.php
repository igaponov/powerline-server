<?php

namespace Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Form\Type\ActivitiesType;
use Civix\CoreBundle\Entity\Activity;
use Civix\CoreBundle\Entity\ActivityRead;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserFollow;
use Civix\CoreBundle\QueryFunction\ActivitiesQuery;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use JMS\DiExtraBundle\Annotation as DI;
use Knp\Component\Pager\Event\AfterEvent;
use Knp\Component\Pager\Pagination\AbstractPagination;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @Route("/activities")
 */
class ActivityController extends FOSRestController
{
    /**
     * @var EntityManagerInterface
     * @DI\Inject("doctrine.orm.entity_manager")
     */
    private $em;

    /**
     * @var TokenStorageInterface
     * @DI\Inject("security.token_storage")
     */
    private $tokenStorage;

    /**
     * Return a user's list of activities
     *
     * @Route("")
     * @Method("GET")
     *
     * @QueryParam(name="following", requirements="\d+", description="Following user id")
     * @QueryParam(name="user", requirements="\d+", description="Another user's id")
     * @QueryParam(name="type", requirements="poll|post|petition", description="Activity types (array)", map=true)
     * @QueryParam(name="group", requirements="\d+", description="Filter by group ID")
     * @QueryParam(name="followed", requirements="0|1", description="Filter by followed users")
     * @QueryParam(name="non_followed", requirements="0|1", description="Filter by non-followed users")
     * @QueryParam(name="page", requirements="\d+", default="1")
     * @QueryParam(name="per_page", requirements="(10|15|20)", default="20")
     *
     * @ApiDoc(
     *     authentication = true,
     *     resource=true,
     *     section="Activity",
     *     output = {
     *          "class" = "array<Civix\CoreBundle\Entity\Activity> as paginator",
     *          "groups" = {"api-activities", "activity-list"},
     *          "parsers" = {
     *              "Civix\ApiBundle\Parser\PaginatorParser"
     *          }
     *     },
     *     description="Return a user's list of activities",
     *     statusCodes={
     *          200="Returned when successful",
     *          405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"paginator", "api-activities", "activity-list", "api-comments", "api-comments-add"})
     *
     * @param ParamFetcher $params
     *
     * @return PaginationInterface
     *
     * @throws \LogicException
     */
    public function getActivitiesAction(ParamFetcher $params): PaginationInterface
    {
        $start = new \DateTime('-30 days');
        $activityTypes = $params->get('type') ? (array)$params->get('type') : null;

        $user = $this->tokenStorage->getToken()->getUser();
        $queryBuilder = new ActivitiesQuery($this->em);
        if ($followingId = $params->get('following')) {
            // get user follow status
            $userFollow = $this->em
                ->getRepository('CivixCoreBundle:UserFollow')->findOneBy([
                    'user' => $followingId,
                    'follower' => $user,
                    'status' => UserFollow::STATUS_ACTIVE,
                ]);
            if (!$userFollow) {
                $query = [];
            } else {
                $query = $queryBuilder(
                    $userFollow->getFollower(),
                    $activityTypes,
                    [
                        $queryBuilder::getFilterByFollowing($userFollow->getUser()),
                        $queryBuilder::getFilterByGroup($params->get('group')),
                        $queryBuilder::getFilterByStartAt($start)
                    ]
                );
            }
        } elseif ($params->get('user')) {
            $following = $this->em
                ->getRepository(User::class)->find($params->get('user'));
            if (!$following) {
                $query = [];
            } else {
                $query = $queryBuilder(
                    $user,
                    $activityTypes,
                    [
                        $queryBuilder::getFilterByFollowing($following),
                        $queryBuilder::getFilterByGroup($params->get('group')),
                        $queryBuilder::getFilterByStartAt($start)
                    ]
                );
            }
        } elseif ($params->get('followed')) {
            $query = $queryBuilder(
                $user,
                $activityTypes,
                [
                    $queryBuilder::getFilterByFollowed($user),
                    $queryBuilder::getFilterByGroup($params->get('group')),
                    $queryBuilder::getFilterByStartAt($start)
                ]
            );
        } elseif ($params->get('non_followed')) {
            $query = $queryBuilder(
                $user,
                $activityTypes,
                [
                    $queryBuilder::getFilterByNonFollowed($user),
                    $queryBuilder::getFilterByGroup($params->get('group')),
                    $queryBuilder::getFilterByStartAt($start)
                ]
            );
        } else {
            $query = $queryBuilder(
                $user,
                $activityTypes,
                [
                    $queryBuilder::getFilterByGroup($params->get('group')),
                    $queryBuilder::getFilterByStartAt($start)
                ]
            );
        }

        $paginator = $this->get('knp_paginator');
        $paginator->connect('knp_pager.after', function (AfterEvent $event) use ($user, $queryBuilder) {
            $filter = function (ActivityRead $activityRead) use ($user) {
                return $activityRead->getUser()->getId() === $user->getId();
            };
            $activities = [];
            $pagination = $event->getPaginationView();
            if (!$pagination instanceof AbstractPagination) {
                return;
            }
            foreach ($pagination as $activity) {
                $zone = $activity['zone'];
                $activity = reset($activity);
                /** @var Activity $activity */
                $activity->setZone($zone);
                if ($activity->getActivityRead()->filter($filter)->count()) {
                    $activity->setRead(true);
                }
                $activities[] = $activity;
            }
            $queryBuilder->runPostQueries($activities);
            $pagination->setItems($activities);
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
     *
     * @throws \Symfony\Component\Form\Exception\AlreadySubmittedException
     */
    public function patchAction(Request $request)
    {
        $form = $this->createForm(ActivitiesType::class);
        $form->submit($request->request->all());
        
        if ($form->isValid()) {
            $user = $this->tokenStorage->getToken()->getUser();

            return $this->get('civix_core.service.activity_manager')
                ->bulkUpdate(new ArrayCollection($form->getData()['activities']), $user);
        }

        return $form;
    }
}
