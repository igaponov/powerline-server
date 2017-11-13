<?php

namespace Civix\ApiBundle\Controller\V2_2;

use Civix\Component\Doctrine\ORM\Cursor;
use Civix\CoreBundle\Entity\CommentedInterface;
use Civix\CoreBundle\QueryFunction\RootCommentsByEntityQuery;
use Doctrine\ORM\EntityManager;
use FOS\RestBundle\Request\ParamFetcher;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

abstract class AbstractCommentsController
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

    protected function getComments(ParamFetcher $params, CommentedInterface $entity): Cursor
    {
        $token = $this->tokenStorage->getToken();
        $query = new RootCommentsByEntityQuery($this->em);

        return $query($entity, $token->getUser(), $params->get('cursor'), $params->get('limit'));
    }
}
