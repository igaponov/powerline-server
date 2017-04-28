<?php
namespace Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\CommentedInterface;
use Civix\CoreBundle\Entity\Post\Comment;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Request\ParamFetcher;
use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Route("/posts/{id}/comments")
 */
class PostCommentsController extends AbstractCommentsController
{
    /**
     * @var \Civix\CoreBundle\Service\CommentManager
     * @DI\Inject("civix_core.comment_manager")
     */
    private $manager;

    protected function getManager()
    {
        return $this->manager;
    }

    /**
     * Get list comments.
     *
     * @Route("", requirements={"id"="\d+"})
     * @Method("GET")
     *
     * @QueryParam(name="parent", requirements="\d+", description="Returns child comments for given parent")
     * @QueryParam(name="page", requirements="\d+", default="1")
     * @QueryParam(name="per_page", requirements=@Assert\Range(min="1", max="20"), default="20")
     * @QueryParam(name="sort", requirements="(default|createdAt)", default="default")
     * @QueryParam(name="sort_dir", requirements="(ASC|DESC)", default="ASC")
     *
     * @ParamConverter("entity", class="Civix\CoreBundle\Entity\Post")
     *
     * @SecureParam("entity", permission="view")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Posts",
     *     description="Get comments",
     *     output="Knp\Component\Pager\Pagination\PaginationInterface",
     *     statusCodes={
     *         403="Access Denied",
     *         404="Post Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"paginator", "api-comments", "api-comments-parent", "comments-rate"})
     *
     * @param ParamFetcher $params
     * @param CommentedInterface $entity
     *
     * @return \Knp\Component\Pager\Pagination\PaginationInterface
     */
    public function getCommentsAction(ParamFetcher $params, CommentedInterface $entity)
    {
        return $this->getComments($params, $entity, Comment::class);
    }

    /**
     * Add comment.
     *
     * @Route("", requirements={"id"="\d+"})
     * @Method("POST")
     *
     * @ParamConverter("entity", class="Civix\CoreBundle\Entity\Post")
     *
     * @SecureParam("entity", permission="view")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Posts",
     *     description="Add comment",
     *     input="\Civix\ApiBundle\Form\Type\CreateCommentType",
     *     output={
     *          "class" = "\Civix\CoreBundle\Entity\BaseComment",
     *          "groups" = {"api-comments", "api-comments-parent"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         400="Bad Request",
     *         403="Access Denied",
     *         404="Post Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api-comments", "api-comments-parent"})
     *
     * @param Request $request
     * @param CommentedInterface $entity
     *
     * @return BaseComment|\Symfony\Component\Form\Form
     */
    public function postCommentsAction(Request $request, CommentedInterface $entity)
    {
        return $this->postComments($request, $entity, Comment::class);
    }
}