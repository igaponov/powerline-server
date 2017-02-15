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
 * @Route("/admin/posts")
 */
class PostController extends Controller
{
    /**
     * @var EntityManager
     * @DI\Inject("doctrine.orm.entity_manager")
     */
    private $em;

    /**
     * @Route("", name="civix_front_post_index")
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
     * @Route("/{id}/delete", name="civix_front_post_delete")
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

            return $this->redirectToRoute('civix_front_post_index');
        }
        throw new BadRequestHttpException();
    }

    /**
     * @Route("/delete", name="civix_front_posts_delete")
     * @Method({"POST"})
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deletePostsAction(Request $request)
    {
        if ($this->isCsrfTokenValid('posts_delete', $request->get('_mass_token'))) {
            $posts = $this->em->getRepository(Post::class)
                ->findAllForDeletionByIds((array)$request->get('post'));
            foreach ($posts as $post) {
                $this->em->remove($post);
            }
            $this->em->flush();

            return $this->redirectToRoute('civix_front_post_index');
        }
        throw new BadRequestHttpException();
    }

    /**
     * @Route("/authors/ban", name="civix_front_posts_authors_ban")
     * @Method({"POST"})
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function banPostAuthorsAction(Request $request)
    {
        if ($this->isCsrfTokenValid('users_ban', $request->get('_mass_token'))) {
            $posts = $this->em->getRepository(Post::class)
                ->findAllWithUserByIds((array)$request->get('post'));
            foreach ($posts as $post) {
                $user = $post->getUser();
                $user->disable();
                $this->em->persist($user);
            }
            $this->em->flush();

            return $this->redirect($request->headers->get('Referer'));
        }
        throw new BadRequestHttpException();
    }
}