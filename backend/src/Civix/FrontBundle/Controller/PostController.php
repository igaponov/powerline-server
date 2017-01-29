<?php

namespace Civix\FrontBundle\Controller;

use Civix\CoreBundle\Entity\Post;
use Doctrine\ORM\EntityManager;
use JMS\DiExtraBundle\Annotation as DI;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class PostController
 * @package Civix\FrontBundle\Controller\Superuser
 * @Route("/admin")
 */
class PostController extends Controller
{
    /**
     * @var EntityManager
     * @DI\Inject("doctrine.orm.entity_manager")
     */
    private $em;

    /**
     * @Route("/posts", name="civix_front_superuser_posts")
     * @Method({"GET"})
     * @Template("CivixFrontBundle:Post:index.html.twig")
     * @param Request $request
     * @return array
     */
    public function indexAction(Request $request)
    {
        $query = $this->em->getRepository(Post::class)
            ->getFindByQuery(['marked_as_spam' => true]);

        $pagination = $this->get('knp_paginator')->paginate(
            $query,
            $request->get('page', 1),
            20
        );

        return compact('pagination');
    }

    /**
     * @Route("/user/{id}/posts", name="civix_front_superuser_user_posts")
     * @Method({"GET"})
     * @Template("CivixFrontBundle:Post:index.html.twig")
     * @param Request $request
     * @param $id
     * @return array
     */
    public function usersPostsAction(Request $request, $id)
    {
        $query = $this->em->getRepository(Post::class)
            ->getFindByQuery(['user' => $id]);

        $pagination = $this->get('knp_paginator')->paginate(
            $query,
            $request->get('page', 1),
            20
        );

        return compact('pagination');
    }

    /**
     * @Route("/posts/{id}/delete", name="civix_front_superuser_post_delete")
     * @Method({"POST"})
     * @param Request $request
     * @param Post $post
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deletePostAction(Request $request, Post $post)
    {
        if ($this->isCsrfTokenValid('post_delete', $request->get('_token'))) {
            $this->em->remove($post);
            $this->em->flush();
            return $this->redirectToRoute('civix_front_superuser_posts');
        }
        throw new BadRequestHttpException();
    }
}