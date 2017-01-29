<?php

namespace Civix\FrontBundle\Controller;

use Civix\CoreBundle\Entity\Group;
use Civix\FrontBundle\Form\Type\LocalRepresentativeType;
use Doctrine\ORM\EntityManager;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Request\ParamFetcher;
use JMS\DiExtraBundle\Annotation as DI;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/admin/local-groups")
 */
class LocalGroupController extends Controller
{
    /**
     * @var EntityManager
     * @DI\Inject("doctrine.orm.entity_manager")
     */
    private $em;

    /**
     * @Route("", name="civix_front_local_groups")
     * @Method({"GET"})
     * @QueryParam(name="id", allowBlank=true, requirements="\d+")
     * @Template("CivixFrontBundle:LocalGroup:index.html.twig")
     * @param ParamFetcher $params
     * @return array
     */
    public function indexAction(ParamFetcher $params)
    {
        $group = $this->em->find(Group::class, $params->get('id'));
        if ($group && ($group->getGroupType() !== Group::GROUP_TYPE_STATE && $group->getGroupType() !== Group::GROUP_TYPE_COUNTRY)) {
            throw $this->createNotFoundException();
        }
        $countryGroups = $this->em->getRepository(Group::class)->findBy([
            'groupType' => Group::GROUP_TYPE_COUNTRY,
        ]);

        return array(
            'selectedGroup' => $group,
            'countryGroups' => $countryGroups,
        );
    }

    /**
     * @Route("/{id}", name="civix_front_local_group")
     * @Method({"GET", "POST"})
     * @Template("CivixFrontBundle::form.html.twig")
     * @param Request $request
     * @param Group $group
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function assignLocalGroup(Request $request, Group $group)
    {
        $form = $this->createForm(LocalRepresentativeType::class, $group);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($group);
            $this->em->flush();
            $this->addFlash('notice', 'Assign to local group is completed');

            return $this->redirectToRoute('civix_front_local_groups',
                ['id' => $group->getParent()->getId()]
            );
        }

        return array(
            'form' => $form->createView(),
        );
    }
}