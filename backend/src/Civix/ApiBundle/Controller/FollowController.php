<?php

namespace Civix\ApiBundle\Controller;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\UserGroup;
use Civix\CoreBundle\Event\UserEvents;
use Civix\CoreBundle\Event\UserFollowEvent;
use Civix\CoreBundle\Repository\UserFollowRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Civix\CoreBundle\Entity\UserFollow;
use Civix\CoreBundle\Entity\User;

/**
 * @Route("/follow")
 */
class FollowController extends BaseController
{
    /**
     * Get followers
     *
     * @Route("/")
     * @Method("GET")
     * @ApiDoc(
     *     section="Follow",
     *     description="Get followers",
     *     statusCodes={
     *         200="Get followers success",
     *         400="Bad request",
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     }
     * )
     */
    public function getAction()
    {
        $follows = $this->getDoctrine()->getRepository(UserFollow::class)
            ->findByUser($this->getUser());

        return $this->createJSONResponse($this->jmsSerialization($follows, ['api-follow', 'api-info']));
    }

    /**
     * Follow a user
     *
     * @Route("/")
     * @Method("POST")
     * @ApiDoc(
     *     section="Follow",
     *     resource=true,
     *     description="Follow a user",
     *     statusCodes={
     *         201="follow request success",
     *         400="Bad request",
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     }
     * )
     * @param Request $request
     * @return Response
     */
    public function postAction(Request $request)
    {
        /** @var UserFollow $follow */
        $follow = $this->jmsDeserialization($request->getContent(), UserFollow::class, ['api-follow-create']);

        /** @var User $user */
        $user = $this->getDoctrine()->getRepository(User::class)->find($follow->getUser()->getId());
        $this->followUser($follow, $user);

        return $this->createJSONResponse($this->jmsSerialization($follow, ['api-follow', 'api-info']), 201);
    }

    /**
     * Approve follow request
     *
     * @Route("/{id}")
     * @Method("PUT")
     * @ApiDoc(
     *     section="Follow",
     *     resource=true,
     *     description="Approve follow request",
     *     statusCodes={
     *         200="Approve follow request success",
     *         400="Bad request",
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     }
     * )
     */
    public function putAction(UserFollow $follow, Request $request)
    {
        if ($this->getUser() !== $follow->getUser()) {
            throw new AccessDeniedHttpException();
        }

        $data = json_decode($request->getContent(), true);
        if (!empty($data) && isset($data['status'])) {
            $follow->setStatus($data['status']);
            if ($follow->getStatus() === $follow::STATUS_ACTIVE) {
                $follow->setDateApproval(new \DateTime());
            }
        }

        $this->getDoctrine()->getManager()->flush($follow);

        return $this->createJSONResponse($this->jmsSerialization($follow, ['api-follow', 'api-info']), 200);
    }

    /**
     * Unfollow a user
     *
     * @Route("/{id}")
     * @Method("DELETE")
     * @ApiDoc(
     *     section="Follow",
     *     resource=true,
     *     description="Unfollow a user",
     *     statusCodes={
     *         204="unfollow request success",
     *         400="Bad request",
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     }
     * )
     */
    public function deleteAction(UserFollow $follow)
    {
        if ($this->getUser() !== $follow->getUser() && $this->getUser() !== $follow->getFollower()) {
            throw new AccessDeniedHttpException();
        }

        $this->getDoctrine()->getManager()->remove($follow);
        $this->getDoctrine()->getManager()->flush($follow);

        return $this->createJSONResponse(null, 204);
    }

    /**
     * Follow all group members
     *
     * @Route(
     *     "/group/{id}",
     *     requirements={"id"="\d+"},
     *     name="api_follow_group_member"
     * )
     * @Method("POST")
     * @ParamConverter("group", class="CivixCoreBundle:Group")
     * @ApiDoc(
     *     section="Follow",
     *     resource=true,
     *     description="Follow group members. This api will automatically follow a group member if group permission is public or private.",
     *     statusCodes={
     *         201="follow request success",
     *         400="Bad request",
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     }
     * )
     * @param Group $group
     * @return Response
     */
    public function followGroupMember(Group $group)
    {
        if ($group->getTransparency() !== Group::GROUP_TRANSPARENCY_PUBLIC
            && $group->getTransparency() !== Group::GROUP_TRANSPARENCY_PRIVATE) {

            throw $this->createNotFoundException("This method just for public or private group only.");
        }

        /** @var User $loggedInUser */
        $loggedInUser = $this->getUser();
        $followingIds = $loggedInUser->getFollowingIds();

        $members = $group->getUsers()->filter(function ($entry) use ($followingIds) {
            return !in_array($entry->getId(), $followingIds);
        });

        /** @var User $ugroup */
        foreach ($members as $ugroup) {
            $this->followUser(null, $ugroup);
        }

        $response = new Response('', 201);
        return $response;
    }

    /**
     * @param UserFollow $follow
     * @param User $user
     */
    private function followUser($follow, $user)
    {
        if ($follow === null)
            $follow = new UserFollow();

        $follow->setFollower($this->getUser())
            ->setStatus(UserFollow::STATUS_PENDING)
            ->setUser($user);

        /** @var UserFollowRepository $userFollowRepo */
        $userFollowRepo = $this->getDoctrine()->getRepository(UserFollow::class);
        $userFollowRepo->handle($follow);

        $this->get('civix_core.social_activity_manager')->sendUserFollowRequest($follow);

        $event = new UserFollowEvent($follow);
        $this->get('event_dispatcher')->dispatch(UserEvents::FOLLOWED, $event);
    }
}
