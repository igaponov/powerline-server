<?php

namespace Civix\FrontBundle\Controller;

use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\UserPetition;
use Doctrine\ORM\EntityManager;
use JMS\DiExtraBundle\Annotation as DI;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @Route("/admin/petitions")
 */
class PetitionController extends Controller
{
    /**
     * @var EntityManager
     * @DI\Inject("doctrine.orm.entity_manager")
     */
    private $em;

    /**
     * @Route("", name="civix_front_petition_index")
     * @Method({"GET"})
     * @Template("CivixFrontBundle:Petition:index.html.twig")
     * @param Request $request
     * @return array
     */
    public function indexAction(Request $request)
    {
        $query = $this->em->getRepository(UserPetition::class)
            ->getFindByQuery(['marked_as_spam' => true]);

        $pagination = $this->get('knp_paginator')->paginate(
            $query,
            $request->get('page', 1),
            20
        );

        return compact('pagination');
    }

    /**
     * @Route("/{id}/delete", name="civix_front_petition_delete")
     * @Method({"POST"})
     * @param Request $request
     * @param UserPetition $petition
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deletePetitionAction(Request $request, UserPetition $petition)
    {
        if ($this->isCsrfTokenValid('petition_delete', $request->get('_token'))) {
            $this->em->remove($petition);
            $this->em->flush();

            return $this->redirectToRoute('civix_front_petition_index');
        }
        throw new BadRequestHttpException();
    }

    /**
     * @Route("/delete", name="civix_front_petitions_delete")
     * @Method({"POST"})
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deletePetitionsAction(Request $request)
    {
        if ($this->isCsrfTokenValid('petitions_delete', $request->get('_mass_token'))) {
            $posts = $this->em->getRepository(UserPetition::class)
                ->findAllForDeletionByIds((array)$request->get('petition'));
            foreach ($posts as $post) {
                $this->em->remove($post);
            }
            $this->em->flush();

            return $this->redirectToRoute('civix_front_petition_index');
        }
        throw new BadRequestHttpException();
    }

    /**
     * @Route("/authors/ban", name="civix_front_petitions_authors_ban")
     * @Method({"POST"})
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function banPetitionAuthorsAction(Request $request)
    {
        if ($this->isCsrfTokenValid('users_ban', $request->get('_mass_token'))) {
            $petitions = $this->em->getRepository(UserPetition::class)
                ->findAllWithUserByIds((array)$request->get('petition'));
            foreach ($petitions as $petition) {
                $user = $petition->getUser();
                $user->disable();
                $this->em->persist($user);
            }
            $this->em->flush();

            return $this->redirect($request->headers->get('Referer'));
        }
        throw new BadRequestHttpException();
    }
}