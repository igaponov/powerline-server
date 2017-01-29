<?php

namespace Civix\FrontBundle\Controller;

use Civix\CoreBundle\Entity\QuestionLimit;
use Civix\FrontBundle\Form\Type\LimitType;
use Doctrine\ORM\EntityManager;
use JMS\DiExtraBundle\Annotation as DI;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/admin/limits")
 */
class LimitController extends Controller
{
    /**
     * @var EntityManager
     * @DI\Inject("doctrine.orm.entity_manager")
     */
    private $em;

    /**
     * @Route("", name="civix_front_limits_index")
     * @Method({"GET"})
     * @Template("CivixFrontBundle:Limit:index.html.twig")
     * @param Request $request
     * @return array
     */
    public function manageLimitsAction(Request $request)
    {
        $query = $this->em
            ->getRepository(QuestionLimit::class)
            ->findAll();

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
     * @Route("/{id}", name="civix_front_limits_edit")
     * @Method({"GET", "POST"})
     * @Template("CivixFrontBundle::form.html.twig")
     * @param Request $request
     * @param QuestionLimit $questionLimit
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function editAction(Request $request, QuestionLimit $questionLimit)
    {
        $form = $this->createForm(LimitType::class, $questionLimit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($questionLimit);
            $this->em->flush();

            $this->addFlash('notice', 'Question\'s limit has been successfully saved');

            return $this->redirectToRoute('civix_front_limits_index');
        }

        return array(
            'form' => $form->createView(),
        );
    }
}