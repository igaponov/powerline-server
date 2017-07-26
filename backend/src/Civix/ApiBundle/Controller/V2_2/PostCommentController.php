<?php
namespace Civix\ApiBundle\Controller\V2_2;

use Civix\Component\Doctrine\ORM\Cursor;
use Civix\CoreBundle\Entity\Post\Comment;
use FOS\RestBundle\Controller\Annotations as REST;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Request\ParamFetcher;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Route("/post-comments/{id}", requirements={"id"="\d+"})
 */
class PostCommentController extends AbstractCommentController
{
    /**
     * Get child comments.
     *
     * @REST\Get("/comments")
     *
     * @ParamConverter(name="comment", options={
     *     "repository_method" = "findOneWithCommentedEntityAndGroup"
     * })
     *
     * @QueryParam(name="cursor", requirements="\d+", default="0")
     * @QueryParam(name="limit", requirements=@Assert\Range(min="1", max="20"), default="20")
     *
     * @Security(expression="is_granted('view', comment)")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Posts",
     *     description="Get comments",
     *     output={
     *          "class" = "array<Civix\CoreBundle\Entity\Post\Comment>",
     *          "groups" = {"api-comments", "api-comments-parent", "comments-rate"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\CollectionParser",
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         403="Access Denied",
     *         404="Post Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api-comments", "api-comments-parent", "comments-rate"})
     *
     * @param ParamFetcher $params
     * @param Comment $comment
     *
     * @return Cursor
     */
    public function getCommentsAction(ParamFetcher $params, Comment $comment): Cursor
    {
        return $this->getComments($params, $comment);
    }
}