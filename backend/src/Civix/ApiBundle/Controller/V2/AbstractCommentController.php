<?php
namespace Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Form\Type\CommentRateType;
use Civix\ApiBundle\Form\Type\CommentType;
use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\BaseCommentRate;
use Civix\CoreBundle\Service\CommentManager;
use FOS\RestBundle\Controller\FOSRestController;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractCommentController extends FOSRestController
{
    /**
     * @return CommentManager
     */
    abstract protected function getManager();

    protected function putComment(Request $request, BaseComment $comment)
    {
        $form = $this->createForm(CommentType::class, $comment);
        $form->submit($request->request->all(), false);

        if ($form->isValid()) {
            return $this->getManager()->saveComment($comment);
        }

        return $form;
    }

    protected function deleteComment(BaseComment $comment)
    {
        $this->getManager()->deleteComment($comment);
    }

    protected function rateComment(Request $request, BaseComment $comment, BaseCommentRate $rate)
    {
        $form = $this->createForm(CommentRateType::class, $rate);
        $form->submit($request->request->all());

        if ($form->isValid()) {
            return $this->getManager()->rateComment($comment, $rate);
        }

        return $form;
    }
}