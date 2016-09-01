<?php
namespace Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\Poll\Comment;
use Civix\CoreBundle\Service\CommentManager;
use FOS\RestBundle\Controller\Annotations\View;
use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/poll-comments/{id}")
 */
class PollCommentController extends AbstractCommentController
{
    /**
     * @var \Civix\CoreBundle\Service\CommentManager
     * @DI\Inject("civix_core.comment_manager")
     */
    private $manager;

    /**
     * Update comment
     *
     * @Route("", requirements={"id"="\d+"})
     * @Method("PUT")
     *
     * @ParamConverter("comment", class="Civix\CoreBundle\Entity\Poll\Comment")
     *
     * @SecureParam("comment", permission="edit")
     *
     * @ApiDoc(
     *     authentication=true,
     *     resource=true,
     *     section="Polls",
     *     description="Update comment",
     *     input="Civix\ApiBundle\Form\Type\UpdateCommentType",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Poll\Comment",
     *          "groups" = {"api-comments", "api-comments-parent"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         400="Bad Request",
     *         403="Access Denied",
     *         404="Comment Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api-comments", "api-comments-parent"})
     *
     * @param Request $request
     * @param BaseComment $comment
     *
     * @return BaseComment|\Symfony\Component\Form\Form
     */
    public function putCommentAction(Request $request, BaseComment $comment)
    {
        return $this->putComment($request, $comment, Comment::class);
    }

    /**
     * Delete comment
     *
     * @Route("", requirements={"id"="\d+"})
     * @Method("DELETE")
     *
     * @ParamConverter("comment", class="Civix\CoreBundle\Entity\Poll\Comment")
     *
     * @SecureParam("comment", permission="delete")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Polls",
     *     description="Delete comment",
     *     statusCodes={
     *         403="Access Denied",
     *         404="Comment Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param BaseComment $comment
     */
    public function deleteCommentAction(BaseComment $comment)
    {
        return $this->deleteComment($comment);
    }

    protected function getManager()
    {
        return $this->manager;
    }
}