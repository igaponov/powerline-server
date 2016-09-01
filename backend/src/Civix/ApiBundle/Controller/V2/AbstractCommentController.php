<?php
namespace Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Form\Type\UpdateCommentType;
use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Service\CommentManager;
use FOS\RestBundle\Controller\FOSRestController;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractCommentController extends FOSRestController
{
    /**
     * @return \Civix\CoreBundle\Service\CommentManager
     */
    abstract protected function getManager();

    protected function putComment(Request $request, BaseComment $comment, $commentClass)
    {
        $form = $this->createForm(new UpdateCommentType($commentClass), $comment);
        $form->submit($request, false);

        if ($form->isValid()) {
            return $this->getManager()->saveComment($comment);
        }

        return $form;
    }

    protected function deleteComment(BaseComment $comment)
    {
        $this->getManager()->deleteComment($comment);
    }
}