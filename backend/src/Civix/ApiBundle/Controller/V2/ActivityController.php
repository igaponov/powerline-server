<?php

namespace Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Form\Type\ActivitiesType;
use Civix\CoreBundle\Entity\Activity;
use Civix\CoreBundle\Entity\ActivityRead;
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
use Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\QuerySubscriber;
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
     * @QueryParam(name="post_id", requirements="\d+", description="Filter by post id")
     * @QueryParam(name="poll_id", requirements="\d+", description="Filter by poll id")
     * @QueryParam(name="petition_id", requirements="\d+", description="Filter by petition id")
     * @QueryParam(name="hash_tag", requirements=".+", description="Filter by hash tag")
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
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getActivitiesAction(ParamFetcher $params): PaginationInterface
    {
        $start = new \DateTime('-30 days');
        $activityTypes = $params->get('type') ? (array)$params->get('type') : null;
        $target = [];
        $user = $this->tokenStorage->getToken()->getUser();
        $activitiesQuery = new ActivitiesQuery($this->em);
        if ($followingId = $params->get('following')) {
            // get user follow status
            $userFollow = $this->em
                ->getRepository('CivixCoreBundle:UserFollow')->findOneBy([
                    'user' => $followingId,
                    'follower' => $user,
                    'status' => UserFollow::STATUS_ACTIVE,
                ]);
            if ($userFollow) {
                $qb = $activitiesQuery(
                    $userFollow->getFollower(),
                    $activityTypes,
                    [
                        $activitiesQuery::getFilterByFollowing($userFollow->getUser()),
                        $activitiesQuery::getFilterByGroup($params->get('group')),
                        $activitiesQuery::getFilterByStartAt($start),
                        $activitiesQuery::getFilterByHashTag($params->get('hash_tag')),
                    ]
                );
            }
        } elseif ($params->get('user')) {
            $following = $this->em
                ->getRepository(User::class)->find($params->get('user'));
            if ($following) {
                $qb = $activitiesQuery(
                    $user,
                    $activityTypes,
                    [
                        $activitiesQuery::getFilterByFollowing($following),
                        $activitiesQuery::getFilterByGroup($params->get('group')),
                        $activitiesQuery::getFilterByStartAt($start),
                        $activitiesQuery::getFilterByHashTag($params->get('hash_tag')),
                    ]
                );
            }
        } elseif ($params->get('followed')) {
            $qb = $activitiesQuery(
                $user,
                $activityTypes,
                [
                    $activitiesQuery::getFilterByFollowed($user),
                    $activitiesQuery::getFilterByGroup($params->get('group')),
                    $activitiesQuery::getFilterByStartAt($start),
                    $activitiesQuery::getFilterByHashTag($params->get('hash_tag')),
                ],
                true
            );
        } elseif ($params->get('non_followed')) {
            $qb = $activitiesQuery(
                $user,
                $activityTypes,
                [
                    $activitiesQuery::getFilterByNonFollowed($user),
                    $activitiesQuery::getFilterByGroup($params->get('group')),
                    $activitiesQuery::getFilterByStartAt($start),
                    $activitiesQuery::getFilterByHashTag($params->get('hash_tag')),
                ]
            );
        } elseif ($params->get('post_id')) {
            $qb = $activitiesQuery(
                $user,
                $activityTypes,
                [
                    $activitiesQuery::getFilterByStartAt($start),
                    $activitiesQuery::getFilterByPostId($params->get('post_id')),
                    $activitiesQuery::getFilterByHashTag($params->get('hash_tag')),
                ]
            );
        } elseif ($params->get('poll_id')) {
            $qb = $activitiesQuery(
                $user,
                $activityTypes,
                [
                    $activitiesQuery::getFilterByStartAt($start),
                    $activitiesQuery::getFilterByPollId($params->get('poll_id')),
                    $activitiesQuery::getFilterByHashTag($params->get('hash_tag')),
                ]
            );
        } elseif ($params->get('petition_id')) {
            $qb = $activitiesQuery(
                $user,
                $activityTypes,
                [
                    $activitiesQuery::getFilterByStartAt($start),
                    $activitiesQuery::getFilterByPetitionId($params->get('petition_id')),
                    $activitiesQuery::getFilterByHashTag($params->get('hash_tag')),
                ]
            );
        } else {
            $qb = $activitiesQuery(
                $user,
                $activityTypes,
                [
                    $activitiesQuery::getFilterByGroup($params->get('group')),
                    $activitiesQuery::getFilterByStartAt($start),
                    $activitiesQuery::getFilterByHashTag($params->get('hash_tag')),
                ]
            );
        }

        $paginator = $this->get('knp_paginator');
        $paginator->connect('knp_pager.after', function (AfterEvent $event) use ($user, $activitiesQuery) {
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
            $activitiesQuery->runPostQueries($activities);
            $pagination->setItems($activities);
        });
        if (isset($qb)) {
            /** @noinspection PhpDeprecationInspection */
            $target = $qb->getQuery()->setHint(QuerySubscriber::HINT_COUNT, $activitiesQuery->getCount($qb));
        }
        return $paginator->paginate(
            $target,
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
