<?php

namespace Civix\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\Group;

/**
 * @Route("/search")
 */
class SearchController extends BaseController
{
    /**
     * @Route("", name="api_search")
     * @Method("GET")
     *
     * @ApiDoc(
     *     resource=true,
     *     description="Get list of search items",
     *     filters={
     *         {"name"="query", "dataType"="string"}
     *     },
     *     statusCodes={
     *         200="Returns list search items",
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     }
     * )
     */
    public function getGroupsAction(Request $request)
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $entityManager = $this->getDoctrine()->getManager();
        $query = $request->get('query');

        if (!$query) {
            return $response;
        }
        $groups = $entityManager->getRepository('CivixCoreBundle:Group')
            ->findByQuery($query, $this->getUser());
        $representatives = $entityManager->getRepository('CivixCoreBundle:Representative')
            ->findByQuery($query, $this->getUser());
        $users = $entityManager->getRepository('CivixCoreBundle:User')
            ->findByQueryForFollow($query, $this->getUser());

        $result = array(
            'groups' => $groups,
            'representatives' => $representatives,
            'users' => $users,
        );

        $response->setContent($this->jmsSerialization($result, array('api-search')));

        return $response;
    }

    /**
     * Deprecated, use `GET /api/v2/micro-petitions?tag=hash` instead.
     *
     * @Route("/by-hash-tags", name="api_search_hash_tag")
     * @Method("GET")
     *
     * @ApiDoc(
     *     section="User Petitions",
     *     description="Search micropetitions by hash tag",
     *     filters={
     *         {"name"="query", "dataType"="string"}
     *     },
     *     statusCodes={
     *         200="Returns list search items",
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     },
     *     deprecated=true
     * )
     */
    public function findByHashTag(Request $request)
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $query = $request->get('query');

        if (!$query) {
            return $response;
        }
        $petitions = $this->getDoctrine()->getRepository('CivixCoreBundle:Micropetitions\Petition')
            ->findActiveByHashTag($query, $this->getUser());
        $response->setContent($this->jmsSerialization($petitions, array('api-petitions-list')));

        return $response;
    }

    /**
     * @Route("/friends", name="api_search_friends")
     * @Method("GET")
     *
     * @ApiDoc(
     *     resource=true,
     *     description="Search friends by email's hash",
     *     filters={
     *         {"name"="emails", "dataType"="array"},
     *         {"name"="limit", "dataType"="integer"},
     *         {"name"="page", "dataType"="integer"}
     *     },
     *     statusCodes={
     *         200="Returns list search items",
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     }
     * )
     * 
     * @param Request $request
     * @return Response
     */
    public function getFriendsAction(Request $request)
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $emails = array_filter((array)$request->get('emails', array()), 'is_string');
        $phones = array_filter((array)$request->get('phones', array()), 'is_string');
        $limit = $request->get('limit', 50);
        $page = $request->get('page', 1);

        if (!$emails && !$phones) {
            return $response;
        }

        $users = $this->getDoctrine()->getRepository('CivixCoreBundle:User')
            ->getUsersByEmailAndPhoneHashes($emails, $phones, $this->getUser()->getId(), $page, $limit);
        $response->setContent($this->jmsSerialization($users, array('api-info')));

        return $response;
    }
}
