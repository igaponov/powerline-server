<?php

namespace Civix\ApiBundle\Controller;

use FOS\RestBundle\Controller\Annotations\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Civix\CoreBundle\Entity\ActivityRead;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserFollow;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

class ActivityController extends BaseController
{
    /**
     * @Route("/activity/", name="api_activity_index")
     * @Method("GET")
     *
     * @deprecated change to one query with sorting on client side (/activities/)
     */
    public function indexAction(Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();

        $start = new \DateTime($request->get('start'));
        $currentDate = new \DateTime();

        $activities = $entityManager->getRepository('CivixCoreBundle:Activity')
            ->findActivities($start, $this->getUser(), $request->get('closed'));

        $response = new Response($this->jmsSerialization($activities, ['api-activities']));
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('Server-Time', $currentDate->format('Y-m-d H:i:s'));

        return $response;
    }

    /**
     * Return a user's list of activities.
     * Deprecated, use /api/v2/activities instead.
     *
     * @Route("/activities")
     * @Method("GET")
     *
     * @ApiDoc(
     *     authentication = true,
     *     resource=true,
     *     section="Activity",
     *     input = "",
     *     output = {
     *          "class" = "ArrayCollection<Civix\CoreBundle\Entity\Activity>",
     *          "groups" = {"api-activities"}
     *     },
     *     filters={
     *     },
     *     parameters={
     *     },
     *     description="Returns an array of activities",
     *     statusCodes={
     *          201="Returned when successful ",
     *          400="",
     *          405="Method Not Allowed"
     *     },
     *     deprecated=true
     * )
     *
     * @View(serializerGroups={"api-activities"})
     *
     * @param Request $request
     * @return Response
     */
    public function getcAction(Request $request)
    {
        $offset = $request->query->get('offset', 0);
        $limit = $request->query->get('limit', 10);

        $start = new \DateTime('-30 days');

        if ($request->query->get('following')) {
            // following var set in request

            // get following users
            $following = $this->getDoctrine()->getRepository(User::class)
                ->find($request->query->get('following'));

            // get user follow status
            $userFollow = $this->getDoctrine()
                ->getRepository('CivixCoreBundle:UserFollow')->findOneBy([
                    'user' => $following,
                    'follower' => $this->getUser(),
                ]);
            if (!$following || !$userFollow ||
                $userFollow->getStatus() !== $userFollow::STATUS_ACTIVE) {
                $activities = [];
            } else {
                $activities = $this->getDoctrine()->getRepository('CivixCoreBundle:Activity')
                    ->findActivitiesByFollowing($following, (int) $offset, (int) $limit);
            }
        } else {
            // get activities by user with offset + limit
            $activities = $this->getDoctrine()->getRepository('CivixCoreBundle:Activity')
                ->findActivitiesByUser($this->getUser(), $start, (int) $offset, (int) $limit);
        }

        return $activities;
    }

    /**
     * Mark an activity as read
     *
     * @Route("/activities/read/")
     * @Method("POST")
     *
     * @ApiDoc(
     * 	   https = true,
     *     authentication = false,
     *     resource=true,
     *     section="Activity",
     *     description="Mark activity read",
     *     views = { "default"},
     *     output = "",
     *     requirements={
     *     },
     *     tags={
     *         "stable" = "#89BF04",
     *         "POST" = "#10a54a",
     *         "activity",
     *     },
     *     filters={
     *     },
     *     parameters={
     *     },
     *     input = {
     *   	"class" = "",
     *	    "options" = {"method" = "POST"},
     *	   },
     *     statusCodes={
     *          201="Returned when successful ",
     *          400="",
     *          405="Method Not Allowed"
     *     }
     * )
     *
     * # TODO: needs input label
     */
    public function saveReadAction(Request $request)
    {
        $items = $this->jmsDeserialization($request->getContent(),
            'array<'.ActivityRead::class.'>', ['api-activities']);

        /* @var ActivityRead $item */
        foreach ($items as $item) {
            $item->setUser($this->getUser())->setCreatedAt(new \DateTime());
        }
        $this->getDoctrine()->getRepository(ActivityRead::class)->save($items);

        return $this->createJSONResponse('[]', 201);
    }
}
