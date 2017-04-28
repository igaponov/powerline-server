<?php

namespace Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Form\Type\CreateCommentType;
use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Entity\CommentedInterface;
use Civix\CoreBundle\Repository\CommentRepository;
use Doctrine\ORM\EntityManager;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractCommentsController extends FOSRestController
{
    /**
     * @return \Civix\CoreBundle\Service\CommentManager
     */
    abstract protected function getManager();

    protected function getComments(ParamFetcher $params, CommentedInterface $entity, $entityClass)
    {
        $entityManager = $this->getDoctrine()->getManager();
        /** @var CommentRepository $repository */
        $repository = $entityManager->getRepository($entityClass);
        $query = $repository->getCommentsByEntityQuery($entity, $this->getUser(), $params->get('parent'));

        return $this->get('knp_paginator')->paginate(
            $query,
            $params->get('page'),
            $params->get('per_page')
        );
    }

    protected function postComments(Request $request, CommentedInterface $entity, $commentClass)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->getDoctrine()->getManager();
        $form = $this->createForm(CreateCommentType::class, null, ['em' => $entityManager, 'data_class' => $commentClass]);
        $form->submit($request->request->all());

        if ($form->isValid()) {
            /** @var BaseComment $comment */
            $comment = $form->getData();
            $comment->setUser($this->getUser());
            $entity->addComment($comment);

            return $this->getManager()->addComment($comment);
        }

        return $form;
    }
}
