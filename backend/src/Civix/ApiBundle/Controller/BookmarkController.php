<?php

namespace Civix\ApiBundle\Controller;

use Civix\CoreBundle\Entity\Bookmark;
use Civix\CoreBundle\Repository\BookmarkRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\Micropetitions;

/**
 * @Route("/bookmarks", name="api_bookmarks")
 */
class BookmarkController extends BaseController
{
    /**
     * Get list of bookmarks
     *
     * @Route(
     *      "/list/{type}/{page}",
     *      requirements={
     *          "page"="\d+",
     *          "type"="petition|petition_comment|petition_answer|poll|poll_comment|poll_answer|post|all"
     *      },
     *      name="api_bookmarks_list"
     * )
     * @Method("GET")
     * @ApiDoc(
     *     resource=true,
     *     description="Get saved items. The saved item can be petition, petition_comment, petition_answer, poll,
     *     poll_comment, poll_answer, or post",
     *     statusCodes={
     *         200="Returns saved items",
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     }
     * )
     * @param $type
     * @param $page
     * @return Response
     */
    public function indexAction($type, $page = 1)
    {
        if ($type !== Bookmark::TYPE_PETITION && $type !== Bookmark::TYPE_PETITION_ANSWER
            && $type !== Bookmark::TYPE_PETITION_COMMENT && $type !== Bookmark::TYPE_POLL
            && $type !== Bookmark::TYPE_POLL_ANSWER && $type !== Bookmark::TYPE_POLL_COMMENT
            && $type !== Bookmark::TYPE_POST && $type !== Bookmark::TYPE_ALL) {

            throw $this->createNotFoundException();
        }

        /** @var BookmarkRepository $bookmarkRepository */
        $bookmarkRepository = $this->getDoctrine()
            ->getManager()
            ->getRepository('CivixCoreBundle:Bookmark');

        $bookmarkQuery = $bookmarkRepository->findByType($type, $this->getUser());

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate($bookmarkQuery, $page);

        $totalItem = $pagination->getTotalItemCount();
        $itemPerPage = $pagination->getItemNumberPerPage();
        $totalPage = ceil($totalItem / $itemPerPage);

        $bookmarks = array(
            'type' => $type,
            'page' => $page,
            'total_pages' => $totalPage,
            'total_items' => $totalItem,
            'items' => array()
        );

        if ($page < 1 || $page > $totalPage)
            throw $this->createNotFoundException("Page out of range");

        foreach($pagination as $row) {
            $bookmarks['items'][] = $row;
        }

        $response = new Response($this->jmsSerialization($bookmarks, ['api-bookmarks']));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Add bookmark
     *
     * @Route(
     *     "/add/{type}/{itemId}",
     *     requirements={
     *          "page"="\d+",
     *          "type"="petition|petition_comment|petition_answer|poll|poll_comment|poll_answer|post|all"
     *      },
     *      name="api_bookmarks_add"
     * )
     * @Method("POST")
     * @ApiDoc(
     *     resource=true,
     *     description="Add saved item. The saved item can be petition, petition_comment, petition_answer, poll,
     *     poll_comment, poll_answer, or post",
     *     statusCodes={
     *         200="Returns saved item",
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     }
     * )
     * @param $type
     * @param $itemId
     * @return Response
     */
    public function add($type, $itemId)
    {
        if ($type !== Bookmark::TYPE_PETITION && $type !== Bookmark::TYPE_PETITION_ANSWER
            && $type !== Bookmark::TYPE_PETITION_COMMENT && $type !== Bookmark::TYPE_POLL
            && $type !== Bookmark::TYPE_POLL_ANSWER && $type !== Bookmark::TYPE_POLL_COMMENT
            && $type !== Bookmark::TYPE_POST) {

            throw $this->createNotFoundException();
        }

        /** @var BookmarkRepository $bookmarkRepository */
        $bookmarkRepository = $this->getDoctrine()->getRepository(Bookmark::class);
        $bookmark = $bookmarkRepository->save($type, $this->getUser(),$itemId);

        $response = new Response($this->jmsSerialization($bookmark, ['api-bookmarks']));
        return $response;
    }

    /**
     * Remove bookmark
     *
     * @Route(
     *     "/remove/{id}",
     *     requirements={"id"="\d+"},
     *     name="api_bookmarks_remove"
     * )
     * @Method("DELETE")
     * @ApiDoc(
     *     resource=true,
     *     description="Delete saved item.",
     *     statusCodes={
     *         200="Returns success or not",
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param $id
     * @return Response
     */
    public function remove($id)
    {
        /** @var BookmarkRepository $bookmarkRepository */
        $bookmarkRepository = $this->getDoctrine()->getRepository(Bookmark::class);
        $bookmark = $bookmarkRepository->remove($id);

        if ($bookmark === false)
            throw $this->createNotFoundException();

        $response = new Response($this->jmsSerialization($bookmark, ['api-bookmarks']));
        return $response;
    }
}
