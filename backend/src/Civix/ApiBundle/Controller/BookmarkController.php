<?php

namespace Civix\ApiBundle\Controller;

use Civix\CoreBundle\Entity\Activity;
use Civix\CoreBundle\Entity\Bookmark;
use Civix\CoreBundle\Repository\BookmarkRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * @author Habibillah <habibillah@gmail.com>
 * @Route("/bookmarks", name="api_bookmarks")
 */
class BookmarkController extends BaseController
{
    /**
     * Get list of bookmarks
     *
     * @author Habibillah <habibillah@gmail.com>
     * @Route(
     *      "/list/{type}/{page}",
     *      requirements={
     *          "page"="\d+",
     *          "type"="petition|user-petition|question|leader-news|payment-request|crowdfunding-payment-request|leader-event|all"
     *      },
     *      name="api_bookmarks_list"
     * )
     * @Method("GET")
     * @ApiDoc(
     *     section="Bookmark",
     *     resource=true,
     *     description="Get saved items. The saved item can be petition, micro-petition, question, leader-news, payment-request, crowdfunding-payment-request, leader-event, all",
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
        if (!in_array($type, $this->allowedTypes(true)))
            throw $this->createNotFoundException();

        /** @var BookmarkRepository $repository */
        $repository = $this->getDoctrine()->getManager()->getRepository(Bookmark::class);
        $result = $repository->findByType($type, $this->getUser(), $page);

        $response = new Response($this->jmsSerialization($result, ['api-bookmarks', 'api-activities']));
        
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Add bookmark
     *
     * @author Habibillah <habibillah@gmail.com>
     * @Route(
     *     "/add/{type}/{itemId}",
     *     requirements={
     *          "itemId"="\d+",
     *          "type"="petition|micro-petition|question|leader-news|payment-request|crowdfunding-payment-request|leader-event"
     *      },
     *      name="api_bookmarks_add"
     * )
     * @Method("POST")
     * @ApiDoc(
     *     section="Bookmark",
     *     resource=true,
     *     description="Add saved item. The saved item can be petition, micro-petition, question, leader-news, payment-request, crowdfunding-payment-request, leader-event",
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
        if (!in_array($type, $this->allowedTypes()))
            throw $this->createNotFoundException();

        /** @var BookmarkRepository $bookmarkRepository */
        $bookmarkRepository = $this->getDoctrine()->getRepository(Bookmark::class);
        $bookmark = $bookmarkRepository->save($type, $this->getUser(),$itemId);

        $response = new Response($this->jmsSerialization($bookmark, ['api-bookmarks', 'api-activities']));
        return $response;
    }

    /**
     * Remove bookmark
     *
     * @author Habibillah <habibillah@gmail.com>
     * @Route(
     *     "/remove/{id}",
     *     requirements={"id"="\d+"},
     *     name="api_bookmarks_remove"
     * )
     * @Method("DELETE")
     * @ApiDoc(
     *     section="Bookmark",
     *     resource=true,
     *     description="Delete saved item.",
     *     statusCodes={
     *         204="Returns success",
     *         404="Returns entity not found",
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
        /** @var BookmarkRepository $repository */
        $repository = $this->getDoctrine()->getRepository(Bookmark::class);
        if ($repository->delete($id) === false)
            throw $this->createNotFoundException();

        $response = new Response('', 204);
        return $response;
    }

    /**
     * @param bool $includeAll
     * @return array
     */
    private function allowedTypes($includeAll = false)
    {
        $types = BookmarkRepository::allowedTypes();
        if ($includeAll)
            $types[] = Activity::TYPE_ALL;

        return array_keys($types);
    }
}
