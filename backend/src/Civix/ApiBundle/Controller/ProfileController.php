<?php

namespace Civix\ApiBundle\Controller;

use Civix\CoreBundle\Event\UserEvents;
use Civix\CoreBundle\Event\UserFollowEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserFollow;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

/**
 * @Route("/profile")
 */
class ProfileController extends BaseController
{
    /**
     * Deprecated, use `GET /api/v2/user` instead
     *
     * @Route("", name="api_profile_index")
     * @Method("GET")
     *
     * @ApiDoc(
     *     section="Users",
     *     description="Profile",
     *     statusCodes={
     *         200="Returns profile info",
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     },
     *     deprecated=true
     * )
     */
    public function indexAction()
    {
        $user = $this->get('security.token_storage')->getToken()->getUser();

        $response = new Response($this->jmsSerialization($user, array('api-profile')));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Deprecated, use `GET /api/v2/users/{id}` instead
     *
     * @Route("/info/{user}", requirements={"user"="\d+"}, name="api_profile_information")
     * @Method("GET")
     * @ParamConverter("user", class="CivixCoreBundle:User")
     * @ApiDoc(
     *     section="Followers",
     *     description="Get information on user",
     *     statusCodes={
     *         200="Get information on user",
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     },
     *     deprecated=true
     * )
     * @param User $user
     * @return Response
     */
    public function getInformationAction(User $user)
    {
        $userFollow = $this->getDoctrine()->getRepository(UserFollow::class)->findOneBy([
            'user' => $user,
            'follower' => $this->getUser(),
        ]);

        $isFollowing = $userFollow && $userFollow->getStatus() === UserFollow::STATUS_ACTIVE;
        $response = new Response($this->jmsSerialization($user, $isFollowing ? ['api-full-info'] : ['api-info']));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Deprecated, use `PUT|DELETE /api/v2/user/followings/{id}` instead
     *
     * @Route(
     *     "/follow/{status}/{targetUser}",
     *     requirements={"targetUser"="\d+", "status"="follow|unfollow|active|reject"},
     *     name="api_profile_follow_unfollow"
     * )
     * @Method("POST")
     * @ParamConverter("targetUser", class="CivixCoreBundle:User")
     *
     * @ApiDoc(
     *     section="Followers",
     *     deprecated=true
     * )
     *
     * @deprecated
     * @param $status
     * @param User $targetUser
     * @return Response
     */
    public function followAction($status, User $targetUser)
    {
        $entityManager = $this->getDoctrine()->getManager();

        $user = $this->getUser();

        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');

        if ($user === $targetUser) {
            return $response->setStatusCode(405);
        }

        $follow = $entityManager->getRepository('CivixCoreBundle:User')
            ->$status($user, $targetUser);
        if ($follow) {
            $entityManager->flush();
            if ('follow' === $status) {
                $event = new UserFollowEvent($follow);
                $this->get('event_dispatcher')->dispatch(UserEvents::FOLLOWED, $event);
            }
            $response->setContent(json_encode(array('success' => 'ok')));
        } else {
            $response->setStatusCode(405);
        }

        return $response;
    }

    /**
     * @Route("/waiting-followers")
     * @Method("GET")
     *
     * @ApiDoc(
     *     section="Followers",
     *     deprecated=true
     * )
     *
     * @deprecated
     */
    public function getWaitingFollowersAction()
    {
        return $this->getFollowersResultsByStatus(UserFollow::STATUS_PENDING);
    }

    /**
     * @Route("/followers")
     * @Method("GET")
     *
     * @ApiDoc(
     *     section="Followers",
     *     deprecated=true
     * )
     *
     * @deprecated
     */
    public function getMyFollowers()
    {
        return $this->getFollowersResultsByStatus(UserFollow::STATUS_ACTIVE);
    }

    /**
     * @Route("/following")
     * @Method("GET")
     *
     * @ApiDoc(
     *     section="Followers",
     *     deprecated=true
     * )
     *
     * @deprecated
     */
    public function getMyFollowing()
    {
        $following = $this->getDoctrine()->getRepository('CivixCoreBundle:UserFollow')
            ->getFollowingByUser($this->getUser());

        $response = new Response($this->jmsSerialization($following, array('api-following', 'api-info')));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route("/following/{targetUser}")
     * @Method("GET")
     *
     * @ApiDoc(
     *     section="Followers",
     *     deprecated=true
     * )
     *
     * @deprecated
     * @param User $targetUser
     * @return Response
     */
    public function getFollowingByUser(User $targetUser)
    {
        $following = $this->getDoctrine()->getRepository('CivixCoreBundle:UserFollow')->findOneBy(array(
            'user' => $targetUser,
            'follower' => $this->getUser(),
        ));
        if (!$following) {
            throw $this->createNotFoundException();
        }

        $response = new Response($this->jmsSerialization($following, array('api-following', 'api-info')));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route("/last-following")
     * @Method("GET")
     *
     * @ApiDoc(
     *     section="Followers",
     *     deprecated=true
     * )
     *
     * @deprecated
     * @param Request $request
     * @return Response
     */
    public function getLastApprovedFollowing(Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();

        $start = new \DateTime($request->get('startDate'));

        $lastApprovedFollowing = $entityManager->getRepository('CivixCoreBundle:UserFollow')
                ->getLastApprovedFollowing($this->getUser(), $start);

        $response = new Response($this->jmsSerialization($lastApprovedFollowing, array('api-following', 'api-info')));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route("/update", name="api_profile_update")
     * @Method("POST")
     *
     * @ApiDoc(
     *     resource=true,
     *     section="Users",
     *     description="Update Profile",
     *     filters={
     *         {"name"="step", "dataType"="integer"}
     *     },
     *     statusCodes={
     *         200="Returns profile info",
     *         400="Incorrect data",
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     }
     * )
     * @param Request $request
     * @return Response
     */
    public function updateAction(Request $request)
    {
        /** @var User $user */
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $entityManager = $this->getDoctrine()->getManager();
        $user->setAvatarPath($request->getScheme().'://'.$request->getHttpHost().'/images/avatars/');

        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');

        /** @var User $new */
        $new = $this->jmsDeserialization($request->getContent(), 'Civix\CoreBundle\Entity\User',
            array('api-profile', 'api-change-password'));
        if ($new->getPlainPassword()) {
            $encoder = $this->get('security.encoder_factory')->getEncoder($user);
            $new->setSalt(base_convert(sha1(uniqid(mt_rand(), true)), 16, 36));
            $password = $encoder->encodePassword($new->getPlainPassword(), $new->getSalt());
            $new->setPassword($password);
        }

        $avatarFileName = $user->getAvatarFileName();
        $isAddressChanged = $new->getAddressQuery() !== $user->getAddressQuery();

        $this->get('civix_core.user_manager')
            ->updateProfileFull($user, $new);

        $errors = $this->getValidator()->validate($user, array('profile'));

        if (count($errors) > 0) {
            $response->setStatusCode(400)->setContent(json_encode(array('errors' => $this->transformErrors($errors))));

            return $response;
        } else {
            if ($isAddressChanged) {
                $this->get('civix_core.user_manager')->updateDistrictsIds($user);
                $this->get('civix_core.group_manager')->autoJoinUser($user);
            }
        }

        $entityManager->persist($user);
        $entityManager->flush();

        if ($avatarFileName !== $user->getAvatarFileName()) {
            $this->get('civix_core.activity_update')->updateOwnerData($user);
        }

        $response->setContent($this->jmsSerialization($user, array('api-profile')));

        return $response;
    }

    /**
     * @Route("/settings", name="api_profile_settings")
     * @Method("POST")
     * @ApiDoc(
     *     section="Users",
     *     description="Update settings of notifications for user",
     *     statusCodes={
     *         200="Returns profile info",
     *         400="Incorrect data",
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     }
     * )
     * @param Request $request
     * @return Response
     */
    public function updateSettings(Request $request)
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');

        $entityManager = $this->getDoctrine()->getManager();
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $userSetting = $this->jmsDeserialization(
            $request->getContent(),
            'Civix\CoreBundle\Entity\User',
            array('api-settings')
        );

        $this->get('civix_core.user_manager')->updateSettings($user, $userSetting);

        $entityManager->persist($user);
        $entityManager->flush();
        $response->setContent($this->jmsSerialization($user, array('api-profile')));

        return $response;
    }

    /**
     * @Route("/facebook-friends", name="api_profile_facebook_friends")
     * @Method("POST")
     * @ApiDoc(
     *     section="Users",
     *     description="Get friends of user from facebook",
     *     statusCodes={
     *         200="Return users by facebook ids",
     *         400="Incorrect data",
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     }
     * )
     * @param Request $request
     * @return Response
     */
    public function getMyFacebookFriends(Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $ids = json_decode($request->getContent());
        $excludeIds = $this->getUser()->getFollowingIds();

        $facebookUsers = $entityManager->getRepository('CivixCoreBundle:User')
                ->getFacebookUsers((array) $ids, $excludeIds);

        $response = new Response($this->jmsSerialization($facebookUsers, array('api-info')));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route("/link-to-facebook", name="api_profile_link_to_facebook")
     * @Method("POST")
     * @ApiDoc(
     *     section="Users",
     *     description="Link to facebook account",
     *     filters={
     *         {"name"="facebook_token", "dataType"="string"},
     *         {"name"="facebook_id", "dataType"="string"}
     *     },
     *     statusCodes={
     *         200="Return user profile",
     *         400="Incorrect data",
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     }
     * )
     * @param Request $request
     * @return Response
     */
    public function linkToFacebook(Request $request)
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        /* @var $user User */
        $user = $this->getUser();

        $user->setFacebookId($request->get('facebook_id'));
        $user->setFacebookToken($request->get('facebook_token'));

        $errors = $this->getValidator()->validate($user, array('facebook'));
        if (count($errors) > 0) {
            $response->setStatusCode(400)->setContent(json_encode(array('errors' => $this->transformErrors($errors))));

            return $response;
        }

        if (!$user->getBirth() && $request->get('birth')) {
            $user->setBirth(new \DateTime($request->get('birth')));
        }

        if ($request->get('avatar_file_name') && !$user->getAvatarFileName()) {
            try {
                $this->get('civix_core.crop_avatar')
                    ->saveSquareAvatarFromPath($user, $request->get('avatar_file_name'));
            } catch (\Exception $e) {
                $this->get('logger')->addError($e->getMessage());
            }
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();

        $response->setStatusCode(200)
            ->setContent($this->jmsSerialization($user, array('api-profile')));

        return $response;
    }

    /**
     * @deprecated
     * @param $status
     * @return Response
     */
    private function getFollowersResultsByStatus($status)
    {
        $entityManager = $this->getDoctrine()->getManager();

        $followers = $entityManager->getRepository('CivixCoreBundle:UserFollow')
                ->getFollowersByFStatus($this->getUser(), $status);

        $response = new Response($this->jmsSerialization($followers, array('api-followers', 'api-info')));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
