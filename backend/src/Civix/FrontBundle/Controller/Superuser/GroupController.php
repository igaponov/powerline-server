<?php

namespace Civix\FrontBundle\Controller\Superuser;

use Civix\CoreBundle\Event\GroupEvent;
use Civix\CoreBundle\Event\GroupEvents;
use Civix\CoreBundle\Exception\MailgunException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Civix\FrontBundle\Form\Type\Poll\QuestionLimit;
use Civix\FrontBundle\Form\Type\Superuser\LocalRepresentative;
use Civix\CoreBundle\Entity\Group;

/**
 * @Route("/group")
 */
class GroupController extends Controller
{
    /**
     * @Route("/limits/{id}", name="civix_front_superuser_group_limits")
     * @Method({"GET"})
     * @Template("CivixFrontBundle:Superuser:limitQuestionEdit.html.twig")
     * @param Group $group
     * @return array
     */
    public function limitsGroupAction(Group $group)
    {
        $questionLimitForm = $this->createForm(new QuestionLimit(), $group);

        return array(
            'questionLimitForm' => $questionLimitForm->createView(),
            'updatePath' => 'civix_front_superuser_group_limits_update',
        );
    }

    /**
     * @Route("/limits/{id}/save", name="civix_front_superuser_group_limits_update")
     * @Method({"POST"})
     * @Template("CivixFrontBundle:Superuser:limitQuestionEdit.html.twig")
     * @param Group $group
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function limitsGroupEditAction(Group $group)
    {
        $entityManager = $this->getDoctrine()->getManager();

        $questionLimitForm = $this->createForm(new QuestionLimit(), $group);
        $questionLimitForm->handleRequest($this->getRequest());

        if ($questionLimitForm->isValid()) {
            $entityManager->persist($group);
            $entityManager->flush();

            $this->get('session')->getFlashBag()->add('notice', 'Question\'s limit has been successfully saved');
        } else {
            return array(
                'questionLimitForm' => $questionLimitForm->createView(),
                'updatePath' => 'civix_front_superuser_group_limits_update',
            );
        }

        return $this->redirect($this->generateUrl('civix_front_superuser_manage_groups'));
    }

    /**
     * @Route("/remove/{id}", name="civix_front_superuser_group_remove")
     * @Method({"POST"})
     * @param Group $group
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function removeGroupAction(Group $group)
    {
        $entityManager = $this->getDoctrine()->getManager();

        /** @var $csrfProvider \Symfony\Component\Form\Extension\Csrf\CsrfProvider\SessionCsrfProvider */
        $csrfProvider = $this->get('form.csrf_provider');

        if ($csrfProvider->isCsrfTokenValid('remove_group_'.$group->getId(), $this->getRequest()->get('_token'))) {
            $event = new GroupEvent($group);
            try {
                $this->get('event_dispatcher')
                    ->dispatch(GroupEvents::BEFORE_DELETE, $event);
            } catch (MailgunException $e) {
                $this->get('session')->getFlashBag()->add('error', 'Something went wrong removing the group from mailgun');

                return $this->redirect($this->generateUrl('civix_front_superuser_manage_groups'));   
            }

            try {
                $entityManager
                    ->getRepository('CivixCoreBundle:Group')
                    ->removeGroup($group);
            } catch (\Exception $e) {
                $this->get('session')->getFlashBag()->add('error', $e->getMessage());

                return $this->redirect($this->generateUrl('civix_front_superuser_manage_groups'));
            }


            $this->get('session')->getFlashBag()->add('notice', 'Group was removed');
        } else {
            $this->get('session')->getFlashBag()->add('error', 'Something went wrong');
        }

        return $this->redirect($this->generateUrl('civix_front_superuser_manage_groups'));
    }

    /**
     * @Route("/local/assign/{group}", name="civix_front_superuser_local_groups_assign")
     * @Method({"GET"})
     * @Template("CivixFrontBundle:Superuser:assignLocalGroups.html.twig")
     * @param Group $group
     * @return array
     */
    public function assignLocalGroup(Group $group)
    {
        $localGroupForm = $this->createForm(new LocalRepresentative($group), $group);

        return array(
            'localGroupForm' => $localGroupForm->createView(),
            'group' => $group,
        );
    }

    /**
     * @Route("/local/assign/{group}", name="civix_front_superuser_local_groups_assign_save")
     * @Method({"POST"})
     * @Template("CivixFrontBundle:Superuser:assignLocalGroups.html.twig")
     * @param Group $group
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function saveAssignLocalGroup(Group $group)
    {
        $entityManager = $this->getDoctrine()->getManager();

        $localGroupForm = $this->createForm(new LocalRepresentative($group), $group);
        $localGroupForm->handleRequest($this->getRequest());

        if ($localGroupForm->isValid()) {
            $entityManager->persist($group);
            $entityManager->flush();

            $this->get('session')->getFlashBag()->add('notice', 'Assign to local group is completed');
        } else {
            return array(
                'localGroupForm' => $localGroupForm->createView(),
                'group' => $group,
            );
        }

        return $this->redirect($this->generateUrl('civix_front_superuser_local_groups_by_parent',
            array('id' => $group->getParent()->getId())
        ));
    }

    /**
     * @Route("/local/{id}", name="civix_front_superuser_local_groups_by_parent")
     * @Method({"GET"})
     * @Template("CivixFrontBundle:Superuser:manageLocalGroups.html.twig")
     * @param Group $parent
     * @return array
     */
    public function localGroupActionByState(Group $parent)
    {
        if ($parent->getGroupType() !== Group::GROUP_TYPE_STATE && $parent->getGroupType() !== Group::GROUP_TYPE_COUNTRY) {
            throw $this->createNotFoundException();
        }

        return array(
            'selectedGroup' => $parent,
            'countryGroups' => $this->getCountryGroups(),
        );
    }

    /**
     * @Route("/local", name="civix_front_superuser_local_groups")
     * @Method({"GET"})
     * @Template("CivixFrontBundle:Superuser:manageLocalGroups.html.twig")
     */
    public function localGroupAction()
    {
        return array(
            'selectedGroup' => null,
            'countryGroups' => $this->getCountryGroups(),
        );
    }

    private function getCountryGroups()
    {
        return $this->getDoctrine()->getRepository(Group::class)->findBy([
            'groupType' => Group::GROUP_TYPE_COUNTRY,
        ]);
    }
}
