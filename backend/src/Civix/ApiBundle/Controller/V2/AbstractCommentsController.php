<?php

namespace Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Form\Type\CreateCommentType;
use Civix\CoreBundle\Entity\CommentedInterface;
use Civix\CoreBundle\Repository\CommentRepository;
use Civix\CoreBundle\Service\CommentManager;
use Doctrine\ORM\EntityManager;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractCommentsController extends FOSRestController
{
    /**
     * @return \Civix\CoreBundle\Service\CommentManager
     */
    abstract protected function getManager(): CommentManager;

    protected function getComments(ParamFetcher $params, CommentedInterface $entity, $entityClass)
    {
        $entityManager = $this->getDoctrine()->getManager();
        /** @var CommentRepository $repository */
        $repository = $entityManager->getRepository($entityClass);
        $query = $repository->getCommentsByEntityQuery(
            $entity,
            $this->getUser(),
            [$params->get('sort') => $params->get('sort_dir')],
            $params->get('parent')
        );

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
        $comment = new $commentClass($this->getUser());
        $form = $this->createForm(CreateCommentType::class, $comment, [
            'em' => $entityManager,
            'data_class' => $commentClass,
        ]);
        $form->submit($request->request->all());

        if ($form->isValid()) {
            $entity->addComment($comment);

            return $this->getManager()->addComment($comment);
        }

        return $form;
    }
}
