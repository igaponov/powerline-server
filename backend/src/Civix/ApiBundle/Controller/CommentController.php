<?php

namespace Civix\ApiBundle\Controller;

use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\Poll;
use Civix\CoreBundle\Entity\Poll\Question\LeaderNews;
use Civix\CoreBundle\Model\Comment\CommentModelInterface;
use FOS\RestBundle\Controller\Annotations as REST;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CommentController extends BaseController
{
    /**
     * Get list comments.
     * Deprecated, use `GET /api/v2/polls/{id}/comments`, `GET /api/v2/user-petitions/{id}/comments`, `GET /api/v2/posts/{id}/comments` instead
     *
     * @Route(
     *      "/{typeEntity}/{entityId}/comments/",
     *      requirements={"entityId"="\d+", "typeEntity" = "poll|micro-petitions|post"},
     *      name="api_comments"
     * )
     * @Method("GET")
     *
     * @ParamConverter(
     *      "commentModel",
     *      class="Civix\CoreBundle\Model\Comment\CommentModelInterface",
     *      options={"typeEntity":"typeEntity"}
     * )
     *
     * @ApiDoc(
     *     section="Polls",
     *     description="Get comments (polls or micropetitions)",
     *     filters={
     *          {"name"="root", "dataType"="boolean", "description"="Show root comment", "default"=false}
     *     },
     *     statusCodes={
     *         200="Returns comments",
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     },
     *     deprecated=true
     * )
     * @param CommentModelInterface $commentModel
     * @param $entityId
     * @param Request $request
     * @return Response
     */
    public function getCommentsAction(CommentModelInterface $commentModel, $entityId, Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();

        if ($request->query->has('root')) {
            $comments = $entityManager
                ->getRepository($commentModel->getRepositoryName())
                ->getRootCommentByEntityId($entityId);
        } else {
            $comments = $entityManager
                ->getRepository($commentModel->getRepositoryName())
                ->getCommentsByEntityId($entityId, $this->getUser());
        }

        $response = new Response($this->jmsSerialization($comments, ['api-comments', 'api-comments-parent']));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Add comment.
     * Deprecated, use `POST /api/v2/polls/{id}/comments`, `POST /api/v2/user-petitions/{id}/comments`, `POST /api/v2/posts/{id}/comments` instead
     *
     * @REST\Post("POST")
     * @Route(
     *      "/{typeEntity}/{entityId}/comments/",
     *      requirements={"entityId"="\d+", "typeEntity" = "poll|micro-petitions|post"},
     *      name="api_comments_add"
     * )
     *
     * @ApiDoc(
     *     section="Polls",
     *     description="Add comment (polls or micropetitions)",
     *     statusCodes={
     *         200="Returns created comment",
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     },
     *     deprecated=true
     * )
     * @param string $typeEntity
     * @param int $entityId
     * @return Response
     */
    public function addCommentAction(string $typeEntity, int $entityId): Response
    {
        switch ($typeEntity) {
            case 'poll':
                $type = 'poll';
                break;
            case 'micro-petitions':
                $type = 'userpetition';
                break;
            case 'post':
                $type = 'post';
                break;
            default:
                throw new \RuntimeException('Invalid comment type.');
        }

        /** @noinspection Symfony2PhpRouteMissingInspection */
        return $this->redirectToRoute("civix_api_v2_{$type}comments_postcomments", ['id' => $entityId]);
    }

    /**
     * Get question comments.
     * Deprecated, use `GET /api/v2/poll-comments/{id}` instead
     *
     * @Route(
     *      "/poll/comments/{questionId}",
     *      requirements={"questionId"="\d+"},
     *      name="api_question_comments"
     * )
     * @Method("GET")
     * @ApiDoc(
     *     resource=true,
     *     section="Polls",
     *     description="Get question comments",
     *     statusCodes={
     *         200="Returns comments",
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     },
     *     deprecated=true
     * )
     *
     * @deprecated Use getCommentsAction
     * @param $questionId
     * @return Response
     */
    public function commentsByQuestionAction($questionId)
    {
        $entityManager = $this->getDoctrine()->getManager();

        $comments = $entityManager
            ->getRepository('CivixCoreBundle:Poll\Comment')
            ->getCommentsByEntityId($questionId, $this->getUser());

        $response = new Response($this->jmsSerialization($comments, ['api-comments', 'api-comments-parent']));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Add question comments.
     * Deprecated, use `POST /api/v2/polls/{id}/comments` instead
     *
     * @Route(
     *      "/poll/comments/add/{id}",
     *      requirements={"id"="\d+"},
     *      name="api_question_add_comment"
     * )
     * @ParamConverter("question", class="CivixCoreBundle:Poll\Question")
     * @Method("POST")
     * @ApiDoc(
     *     section="Polls",
     *     description="Add comment to question",
     *     statusCodes={
     *         200="Returns comments",
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     },
     *     deprecated=true
     * )
     *
     * @deprecated Use getCommentsAction
     * @param Request $request
     * @param Poll\Question $question
     * @return Response
     */
    public function addCommentToQuestion(Request $request, Poll\Question $question)
    {
        $entityManager = $this->getDoctrine()->getManager();

        /* @var Poll\Comment $comment */
        $comment = $this->jmsDeserialization(
            $request->getContent(),
            'Civix\CoreBundle\Entity\Poll\Comment',
            ['api-comments-add', 'api-comments-parent']
        );

        $parentComment = $entityManager
            ->getRepository('CivixCoreBundle:Poll\Comment')
            ->find($comment->getParentComment());

        if (is_null($parentComment)) {
            throw new BadRequestHttpException('Incorrect parent comment');
        }

        $comment->setParentComment($parentComment);
        $comment->setUser($this->getUser());
        $comment->setQuestion($question);

        $entityManager->persist($comment);
        $entityManager->flush();
        if ($question instanceof LeaderNews) {
            $this->get('civix_core.activity_update')->updateResponsesQuestion($question);
        }

        $response = new Response($this->jmsSerialization($comment, ['api-comments', 'api-comments-parent']));

        return $response;
    }

    /**
     * Update comment
     * Deprecated, use `PUT /api/v2/poll-comments/{id}`, `PUT /api/v2/post-comments/{id}`, `PUT /api/v2/user-petition-comments/{id}` instead
     *
     * @Route(
     *      "/{typeEntity}/{entityId}/comments/{id}",
     *      requirements={
     *          "entityId"="\d+",
     *          "typeEntity"="poll|micro-petitions|post",
     *          "id"="\d+"
     *      },
     *      name="api_comments_update"
     * )
     * @Method("PUT")
     * @ParamConverter(
     *      "commentModel",
     *      class="Civix\CoreBundle\Model\Comment\CommentModelInterface",
     *      options={"typeEntity":"typeEntity"}
     * )
     * @ApiDoc(
     *     section="Polls",
     *     description="Update comment (polls or micropetitions)",
     *     statusCodes={
     *         200="Returns updated comment",
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     },
     *     deprecated=true
     * )
     * @param Request $request
     * @param CommentModelInterface $commentModel
     * @param $entityId
     * @return Response
     */
    public function putCommentAction(Request $request, CommentModelInterface $commentModel, $entityId)
    {
        $entityManager = $this->getDoctrine()->getManager();
        /* @var \Civix\CoreBundle\Entity\BaseComment $updated */
        $updated = $this->jmsDeserialization(
            $request->getContent(),
            $commentModel->getRepositoryName(),
            ['api-comments-update']
        );

        $this->validate($updated, null, ['api-comments-update']);
        /** @var BaseComment $comment */
        $comment = $entityManager->getRepository($commentModel->getRepositoryName())
            ->find($request->get('id'));
        if (!$comment || $commentModel->getEntityForComment($comment)->getId() != $entityId || $this->getUser() !== $comment->getUser()) {
            throw $this->createNotFoundException();
        }
        $comment->setCommentBody($updated->getCommentBody());
        $comment->setPrivacy($updated->getPrivacy());

        $this->get('civix_core.comment_manager')->saveComment($comment);

        $response = new Response($this->jmsSerialization(
            $comment,
            ['api-comments']
        ));

        return $response;
    }

    /**
     * Delete comment
     * Deprecated, use `DELETE /api/v2/poll-comments/{id}`, `DELETE /api/v2/post-comments/{id}`, `DELETE /api/v2/user-petition-comments/{id}` instead
     *
     * @Route(
     *      "/{typeEntity}/{entityId}/comments/{id}",
     *      requirements={
     *          "entityId"="\d+",
     *          "typeEntity"="poll|micro-petitions|post",
     *          "id"="\d+"
     *      },
     *      name="api_comments_delete"
     * )
     * @Method("DELETE")
     * @ParamConverter(
     *      "commentModel",
     *      class="Civix\CoreBundle\Model\Comment\CommentModelInterface",
     *      options={"typeEntity":"typeEntity"}
     * )
     * @ApiDoc(
     *     resource=true,
     *     section="Polls",
     *     description="Delete comment (polls or micropetitions)",
     *     statusCodes={
     *         200="Returns comment",
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     },
     *     deprecated=true
     * )
     * @param Request $request
     * @param CommentModelInterface $commentModel
     * @param $entityId
     * @return Response
     */
    public function deleteCommentAction(Request $request, CommentModelInterface $commentModel, $entityId)
    {
        $entityManager = $this->getDoctrine()->getManager();
        /** @var BaseComment $comment */
        $comment = $entityManager->getRepository($commentModel->getRepositoryName())
            ->find($request->get('id'));
        if (!$comment || $commentModel->getEntityForComment($comment)->getId() != $entityId || $this->getUser() !== $comment->getUser()) {
            throw $this->createNotFoundException();
        }
        $comment->setCommentBody('Deleted by author');

        $this->get('civix_core.comment_manager')->saveComment($comment);

        $response = new Response($this->jmsSerialization(
            $comment,
            ['api-comments']
        ));

        return $response;
    }
}
