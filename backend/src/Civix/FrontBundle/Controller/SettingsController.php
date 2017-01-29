<?php

namespace Civix\FrontBundle\Controller;

use Civix\CoreBundle\Entity\State;
use Civix\FrontBundle\Form\Model\CoreSettings;
use Civix\FrontBundle\Form\Type\SettingsType;
use Doctrine\ORM\EntityManager;
use JMS\DiExtraBundle\Annotation as DI;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/admin/settings")
 */
class SettingsController extends Controller
{
    /**
     * @var EntityManager
     * @DI\Inject("doctrine.orm.entity_manager")
     */
    private $em;

    /**
     * @Route("", name="civix_front_superuser_settings_states")
     * @Method({"GET", "POST"})
     * @Template("CivixFrontBundle:Settings:index.html.twig")
     * @param Request $request
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function indexAction(Request $request)
    {
        $settingsForm = $this->createForm(SettingsType::class, new CoreSettings($this->get('civix_core.settings')));
        $query = $this->em
            ->getRepository(State::class)
            ->getStatesWithRepresentative();

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->get('page', 1),
            10,
            array('distinct' => false)
        );

        if ('POST' === $request->getMethod()) {
            if ($settingsForm->submit($request->request->all())->isValid()) {
                $settingsForm->getData()->save();
                $this->addFlash('notice', 'The settings have been updated.');

                return $this->redirectToRoute('civix_front_superuser_settings_states');
            }
        }

        return [
            'settingsForm' => $settingsForm->createView(),
            'pagination' => $pagination,
        ];
    }

    /**
     * @Route("/{code}", name="civix_front_settings_edit")
     * @Method({"POST"})
     * @param Request $request
     * @param State $state
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function editAction(Request $request, State $state)
    {
        if ($this->isCsrfTokenValid(
            'state_repr_update_'.$state->getCode(), $request->get('_token')
        )) {
            $this->get('civix_core.queue_task')
                ->addToQueue(
                    'Civix\CoreBundle\Service\Representative\RepresentativeManager',
                    'synchronizeByStateCode',
                    array($state->getCode())
                );
            $this->addFlash('notice', 'The representatives of the State will be updated.');
        } else {
            $this->addFlash('error', 'State is not found');
        }

        return $this->redirectToRoute('civix_front_superuser_settings_states');
    }
}
