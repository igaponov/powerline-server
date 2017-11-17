<?php

namespace Civix\ApiBundle\Controller\V2_2;

use Civix\Component\Cursor\Event\CursorEvents;
use Civix\Component\Cursor\Event\ItemsEvent;
use Civix\Component\Doctrine\ORM\Cursor;
use Civix\CoreBundle\Entity\Activity;
use Civix\CoreBundle\Entity\ActivityRead;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserFollow;
use Civix\CoreBundle\QueryFunction\ActivitiesQuery;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Request\ParamFetcher;
use JMS\DiExtraBundle\Annotation as DI;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Route("/activities")
 */
class ActivityController
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

    public function __construct(
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage
    ) {
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
    }

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
     * @QueryParam(name="cursor", requirements="\d+", default="0")
     * @QueryParam(name="limit", requirements=@Assert\Range(min="1", max="20"), default="20")
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
     * @View(serializerGroups={"paginator", "api-activities", "activity-list"})
     *
     * @param ParamFetcher $params
     *
     * @return array|Cursor
     */
    public function getActivitiesAction(ParamFetcher $params): Cursor
    {
        $start = new \DateTime('-30 days');
        $activityTypes = $params->get('type') ? (array)$params->get('type') : null;
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

        if (!isset($qb)) {
            return [];
        }

        $cursor = new Cursor($qb, $params->get('cursor'), $params->get('limit'));
        $cursor->connect(CursorEvents::ITEMS, function (ItemsEvent $event) use ($user, $activitiesQuery) {
            $filter = function (ActivityRead $activityRead) use ($user) {
                return $activityRead->getUser()->getId() === $user->getId();
            };
            $activities = [];
            $items = $event->getItems();
            foreach ($items as $activity) {
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
            $event->setItems($activities);
        });

        return $cursor;
    }
}
