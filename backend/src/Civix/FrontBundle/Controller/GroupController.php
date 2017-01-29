<?php

namespace Civix\FrontBundle\Controller;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Event\GroupEvent;
use Civix\CoreBundle\Event\GroupEvents;
use Civix\CoreBundle\Exception\MailgunException;
use Civix\FrontBundle\Form\Type\LimitType;
use Doctrine\ORM\EntityManager;
use JMS\DiExtraBundle\Annotation as DI;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/admin/groups")
 */
class GroupController extends Controller
{
    /**
     * @var EntityManager
     * @DI\Inject("doctrine.orm.entity_manager")
     */
    private $em;

    /**
     * @Route("", name="civix_front_groups")
     * @Method({"GET"})
     * @Template("CivixFrontBundle:Group:index.html.twig")
     * @param Request $request
     * @return array
     */
    public function indexAction(Request $request)
    {
        $query = $this->em
            ->getRepository(Group::class)
            ->getQueryGroupOrderedById();

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->get('page', 1),
            20
        );

        return array(
            'pagination' => $pagination,
        );
    }

    /**
     * @Route("/{id}", name="civix_front_group")
     * @Method({"GET", "POST"})
     * @Template("CivixFrontBundle::form.html.twig")
     * @param Request $request
     * @param Group $group
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function editAction(Request $request, Group $group)
    {
        $form = $this->createForm(LimitType::class, $group);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($group);
            $this->em->flush();

            $this->addFlash('notice', 'Question\'s limit has been successfully saved');

            return $this->redirectToRoute('civix_front_groups');
        }

        return array(
            'form' => $form->createView(),
        );
    }

    /**
     * @Route("/{id}/delete", name="civix_front_group_delete")
     * @Method({"POST"})
     * @param Request $request
     * @param Group $group
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function removeAction(Request $request, Group $group)
    {
        $entityManager = $this->getDoctrine()->getManager();

        if ($this->isCsrfTokenValid('remove_group_'.$group->getId(), $request->get('_token'))) {
            $event = new GroupEvent($group);
            try {
                $this->get('event_dispatcher')
                    ->dispatch(GroupEvents::BEFORE_DELETE, $event);
            } catch (MailgunException $e) {
                $this->addFlash('error', 'Something went wrong removing the group from mailgun');

                return $this->redirectToRoute('civix_front_groups');
            }

            try {
                $entityManager
                    ->getRepository('CivixCoreBundle:Group')
                    ->removeGroup($group);
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());

                return $this->redirect($this->generateUrl('civix_front_groups'));
            }


            $this->addFlash('notice', 'Group was removed');
        } else {
            $this->addFlash('error', 'Something went wrong');
        }

        return $this->redirectToRoute('civix_front_groups');
    }
}
