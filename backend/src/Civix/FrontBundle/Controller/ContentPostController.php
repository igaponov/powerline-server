<?php

namespace Civix\FrontBundle\Controller;

use Civix\CoreBundle\Entity\Content\Post;
use Civix\FrontBundle\Form\Type\PostType as PostType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/admin/content-post")
 */
class ContentPostController extends Controller
{
    /**
     * @Route("", name="civix_front_superuser_post_index")
     * @Method({"GET"})
     * @Template("CivixFrontBundle:ContentPost:index.html.twig")
     * @param Request $request
     * @return array
     */
    public function indexAction(Request $request)
    {
        /* @var $repository \Civix\CoreBundle\Repository\Content\PostRepository */
        $repository = $this->getDoctrine()->getRepository('CivixCoreBundle:Content\Post');

        $query = $repository->getPostQueryByStatus(true);

        $paginator = $this->get('knp_paginator');
        $paginationPublished = $paginator->paginate(
            $query,
            $request->get('page_published', 1),
            10,
            array(
                'pageParameterName' => 'page_published',
                'sortDirectionParameterName' => 'dir_published',
                'sortFieldParameterName' => 'sort_published',
            )
        );

        $query = $repository->getPostQueryByStatus(false);

        $paginationNew = $paginator->paginate(
            $query,
            $request->get('page_new', 1),
            10,
            array(
                'pageParameterName' => 'page_new',
                'sortDirectionParameterName' => 'dir_new',
                'sortFieldParameterName' => 'sort_new',
            )
        );

        return array(
            'paginationPublished' => $paginationPublished,
            'paginationNew' => $paginationNew,
        );
    }

    /**
     * @Route("/new", name="civix_front_superuser_post_new")
     * @Template("CivixFrontBundle:ContentPost:new.html.twig")
     * @param Request $request
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function newAction(Request $request)
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);

        if ('POST' === $request->getMethod()) {
            if ($form->submit($request->request->all())->isValid()) {
                $manager = $this->getDoctrine()->getManager();
                $manager->persist($post);
                $manager->flush();
                $this->get('session')->getFlashBag()->add('notice', 'The post has been successfully saved');

                return $this->redirect($this->generateUrl("civix_front_superuser_post_index"));
            }
        }

        return array(
            'form' => $form->createView(),
        );
    }

    /**
     * @Route("/{id}", requirements={"id"="\d+"}, name="civix_front_superuser_post_edit")
     * @Template("CivixFrontBundle:ContentPost:edit.html.twig")
     * @param Post $post
     * @param Request $request
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function editAction(Post $post, Request $request)
    {
        $form = $this->createForm(PostType::class, $post);

        if ('POST' === $request->getMethod()) {
            if ($form->submit($request->request->all())->isValid()) {
                $manager = $this->getDoctrine()->getManager();
                $manager->persist($post);
                $manager->flush();
                $this->get('session')->getFlashBag()->add('notice', 'The post has been successfully updated');

                return $this->redirect($this->generateUrl("civix_front_superuser_post_index"));
            }
        }

        return array(
            'form' => $form->createView(),
            'post' => $post,
        );
    }

    /**
     * @Route("/delete/{id}", requirements={"id"="\d+"}, name="civix_front_post_delete")
     * @param Post $post
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Post $post)
    {
        $manager = $this->getDoctrine()->getManager();
        $manager->remove($post);
        $manager->flush();
        $this->get('session')->getFlashBag()->add('notice', 'The post has been successfully removed');

        return $this->redirect($this->generateUrl("civix_front_superuser_post_index"));
    }

    /**
     * @Route("/publish/{id}", requirements={"id"="\d+"}, name="civix_front_superuser_post_publish")
     * @param Post $post
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function publishAction(Post $post)
    {
        $post->setPublishedAt(new \DateTime());
        $post->setIsPublished(true);

        $this->getDoctrine()->getManager()->persist($post);
        $this->getDoctrine()->getManager()->flush();
        $this->get('session')->getFlashBag()->add('notice', 'The post has been successfully published');

        return $this->redirect($this->generateUrl("civix_front_superuser_post_index"));
    }
}
