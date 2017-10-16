<?php

namespace Civix\FrontBundle\Controller;

use Civix\CoreBundle\Entity\UserRepresentative;
use Civix\FrontBundle\Form\Type\LimitType;
use Civix\FrontBundle\Form\Type\RepresentativeType;
use Doctrine\ORM\EntityManager;
use JMS\DiExtraBundle\Annotation as DI;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class RepresentativeController
 * @package Civix\FrontBundle\Controller\Superuser
 * @Route("/admin/representatives")
 */
class RepresentativeController extends Controller
{
    /**
     * @var EntityManager
     * @DI\Inject("doctrine.orm.entity_manager")
     */
    private $em;

    /**
     * @Route("/approvals", name="civix_front_representative_approvals")
     * @Method({"GET"})
     * @Template("CivixFrontBundle:Representative:approvals.html.twig")
     * @param Request $request
     * @return array
     */
    public function approvalsAction(Request $request): array
    {
        $query = $this->em
            ->getRepository(UserRepresentative::class)
            ->getQueryRepresentativeByStatus(UserRepresentative::STATUS_PENDING);

        $pagination = $this->get('knp_paginator')->paginate(
            $query,
            $request->get('page', 1),
            20
        );

        return compact('pagination');
    }

    /**
     * @Route("", name="civix_front_representative_index")
     * @Method({"GET"})
     * @Template("CivixFrontBundle:Representative:index.html.twig")
     * @param Request $request
     * @return array
     */
    public function indexAction(Request $request): array
    {
        $query = $this->em
            ->getRepository(UserRepresentative::class)
            ->createQueryBuilder('r')
            ->getQuery();

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
     * @Route("/{id}", name="civix_front_representative_edit", requirements={"id" = "\d+"})
     * @Method({"GET", "POST"})
     * @Template("CivixFrontBundle::form.html.twig")
     * @param Request $request
     * @param UserRepresentative $representative
     * @return array|RedirectResponse
     */
    public function editAction(Request $request, UserRepresentative $representative)
    {
        $form = $this->createForm(RepresentativeType::class, $representative);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($representative);
            $this->em->flush();

            $this->addFlash('notice', 'Representative was saved');

            return $this->redirectToRoute('civix_front_representative_edit', ['id' => $representative->getId()]);
        }

        return array(
            'form' => $form->createView(),
            'form_title' => 'Edit representative',
        );
    }

    /**
     * @Route("/{id}/delete", name="civix_front_representative_delete")
     * @Method({"POST"})
     * @param Request $request
     * @param UserRepresentative $representative
     * @return RedirectResponse
     */
    public function deleteAction(Request $request, UserRepresentative $representative): RedirectResponse
    {
        if ($this->isCsrfTokenValid(
            'representative_delete_'.$representative->getId(), $request->get('_token')
        )) {
            $this->em->remove($representative);
            $this->em->flush();

            $this->addFlash('notice', 'Representative was removed');
        } else {
            $this->addFlash('error', 'Representative is not found');
        }

        return $this->redirectToRoute('civix_front_representative_approvals');
    }

    /**
     * @Route("/{id}/approve", name="civix_front_representative_approve")
     * @Method({"POST"})
     * @param Request $request
     * @param UserRepresentative $representative
     * @return RedirectResponse
     */
    public function approveAction(Request $request, UserRepresentative $representative): RedirectResponse
    {
        if ($this->isCsrfTokenValid(
            'representative_approve_'.$representative->getId(), $request->get('_token')
        )) {
            $representativeManager = $this->get('civix_core.representative_manager');

            //approve representative
            if (!$representativeManager->approveRepresentative($representative)) {
                $this->addFlash('error',
                    'Representative\'s address is not found in Cicero API'
                );

                return $this->redirectToRoute('civix_front_representative_approvals');
            }

            $this->em->persist($representative);
            $this->em->flush();

            //send notification
            $this->get('civix_core.email_sender')
                ->sendToApprovedRepresentative($representative);

            $this->addFlash('notice', 'Representative was approved');
        } else {
            $this->addFlash('error', 'Representative is not found');
        }

        return $this->redirectToRoute('civix_front_representative_approvals');
    }

    /**
     * @Route("/{id}/limit", name="civix_front_representative_limit")
     * @Method({"GET", "POST"})
     * @Template("CivixFrontBundle::form.html.twig")
     * @param Request $request
     * @param UserRepresentative $representative
     * @return array|RedirectResponse
     */
    public function limitAction(Request $request, UserRepresentative $representative)
    {
        $form = $this->createForm(LimitType::class, $representative);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($representative);
            $this->em->flush();

            $this->addFlash('notice', 'Question\'s limit has been successfully saved');

            return $this->redirectToRoute('civix_front_representative_index');
        }

        return array(
            'form' => $form->createView(),
        );
    }
}