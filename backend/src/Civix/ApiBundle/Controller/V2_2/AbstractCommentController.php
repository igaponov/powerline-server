<?php

namespace Civix\ApiBundle\Controller\V2_2;

use Civix\Component\Doctrine\ORM\Cursor;
use Civix\CoreBundle\Entity\BaseComment;
use Civix\CoreBundle\Repository\CommentRepository;
use Doctrine\ORM\EntityManager;
use FOS\RestBundle\Request\ParamFetcher;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

abstract class AbstractCommentController
{
    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(EntityManager $em, TokenStorageInterface $tokenStorage)
    {
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
    }

    protected function getComments(ParamFetcher $params, BaseComment $comment): Cursor
    {
        $token = $this->tokenStorage->getToken();
        /** @var CommentRepository $repository */
        $repository = $this->em->getRepository(get_class($comment));

        return $repository->getChildCommentsCursor($comment, $token->getUser(), $params->get('cursor'), $params->get('limit'));
    }
}
