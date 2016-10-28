<?php

namespace Civix\FrontBundle\Controller;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Event\GroupEvent;
use Civix\CoreBundle\Event\GroupEvents;
use Civix\FrontBundle\Form\Type\CropImage;
use Civix\FrontBundle\Form\Type\Group\Avatar;
use Civix\FrontBundle\Form\Type\Group\Profile;
use Civix\FrontBundle\Form\Type\Group\Registration;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Email;

/**
 * Group controller.
 *
 * @author Valentin Shevko <valentin.shevko@intellectsoft.org>
 */
class GroupController extends Controller
{
    /**
     * @Route("/", name="civix_front_group_index")
     * @Method({"GET"})
     * @Template()
     */
    public function indexAction()
    {
        return array();
    }

    /**
     * @Route("/registration", name="civix_front_group_registration")
     * @Method({"GET", "POST"})
     */
    public function registrationAction()
    {
        throw $this->createNotFoundException();
    }

    /**
     * @Route("/login", name="civix_front_group_login")
     * @Method({"GET"})
     * @Template()
     */
    public function loginAction()
    {
        $request = $this->getRequest();
        $session = $request->getSession();
        $csrfToken = $this->container->get('form.csrf_provider')->generateCsrfToken('group_authentication');

        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
            $session->remove(SecurityContext::AUTHENTICATION_ERROR);
        }

        return array(
            'last_username' => $session->get(SecurityContext::LAST_USERNAME),
            'error' => $error,
            'csrf_token' => $csrfToken,
        );
    }

    /**
     * @Route("/edit-profile", name="civix_front_group_edit_profile")
     * @Method({"GET"})
     * @Template()
     */
    public function editProfileAction()
    {
        $group = $this->getUser();
        $avatarForm = $this->createForm(new Avatar(), $group);
        $profileForm = $this->createForm(new Profile(), $group);

        return array(
            'avatarForm' => $avatarForm->createView(),
            'profileForm' => $profileForm->createView(),
        );
    }

    /**
     * @Route("/update-profile", name="civix_front_group_update_profile")
     * @Method({"POST"})
     * @Template("CivixFrontBundle:Group:editProfile.html.twig")
     */
    public function updateProfileAction()
    {
        $group = $this->getUser();
        $form = $this->createForm(new Profile(), $group);
        $form->handleRequest($this->getRequest());

        if ($form->isValid()) {
            /** @var $entityManager \Doctrine\ORM\EntityManager */
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($group);
            $entityManager->flush();
            $this->get('civix_core.activity_update')->updateOwnerData($group);
            $this->get('session')->getFlashBag()->add('notice', 'Changes have been successfully saved');
        }

        return array(
            'avatarForm' => $this->createForm(new Avatar(), $group)->createView(),
            'profileForm' => $form->createView(),
        );
    }

    /**
     * @Route("/crop-avatar", name="civix_front_group_crop_avatar")
     * @Method({"POST"})
     */
    public function cropAvatarAction()
    {
        /** @var $group Group */
        $group = $this->getUser();
        $avatarForm = $this->createForm(new Avatar(), $group);
        $avatarForm->bind($this->getRequest());

        if ($avatarForm->isValid()) {
            $cropImageForm = $this->createForm(new CropImage());
            $group->setAvatar($group->getAvatarSource());
            $this->get('vich_uploader.storage')->upload($group);

            /** @var $entityManager \Doctrine\ORM\EntityManager */
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($group);
            $entityManager->flush($group);

            return $this->render('CivixFrontBundle:Group:cropAvatar.html.twig', array(
                    'avatarForm' => $avatarForm->createView(),
                    'cropImageForm' => $cropImageForm->createView(),
                ));
        } else {
            $profileForm = $this->createForm(new Profile(), $group);

            return $this->render('CivixFrontBundle:Group:editProfile.html.twig', array(
                    'avatarForm' => $avatarForm->createView(),
                    'profileForm' => $profileForm->createView(),
                ));
        }
    }

    /**
     * @Route("/update-avatar", name="civix_front_group_update_avatar")
     * @Method({"POST"})
     */
    public function updateAvatarAction()
    {
        /** @var $group Group */
        $group = $this->getUser();
        $cropImageForm = $this->createForm(new CropImage());
        $cropImageForm->bind($this->getRequest());
        $cropData = $cropImageForm->getData();

        $this->get('civix_core.crop_avatar')
            ->crop($group, $cropData['x'], $cropData['y'], $cropData['w'], $cropData['h']);

        /** @var $entityManager \Doctrine\ORM\EntityManager */
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($group);
        $entityManager->flush();
        $this->get('civix_core.activity_update')->updateOwnerData($group);

        $this->get('session')->getFlashBag()->add('notice', 'Avatar have been successfully saved');

        return $this->redirect($this->generateUrl('civix_front_group_edit_profile'));
    }

    /**
     * @Route("/invite", name="civix_front_group_invite")
     * @Method({"GET"})
     * @Template("CivixFrontBundle:Group:invite.html.twig")
     */
    public function inviteAction()
    {
        $inviteForm = $this->createForm(new \Civix\FrontBundle\Form\Type\Group\Invite());

        return array(
            'inviteForm' => $inviteForm->createView(),
        );
    }

    /**
     * @Route("/invite/send", name="civix_front_group_send_invite")
     * @Method({"POST"})
     * @Template("CivixFrontBundle:Group:invite.html.twig")
     */
    public function sendInviteAction()
    {
        $entityManager = $this->getDoctrine()->getManager();
        $formData = $this->getRequest()->get('invite');
        $emails = array_map('trim', explode(',', $formData['emails']));

        $errorList = $this->get('validator')
                ->validateValue($emails, new All(array(new Email())));

        if (count($errorList) > 0) {
            $badEmails = array();
            foreach ($errorList as $error) {
                $badEmails[] = $error->getInvalidValue();
            }

            $inviteForm = $this->createForm(new \Civix\FrontBundle\Form\Type\Group\Invite());
            $inviteForm->bind($this->getRequest());

            $this->get('session')->getFlashBag()
                ->add('error', 'Not a valid email address: '.implode(',', $badEmails));

            return array(
                'inviteForm' => $inviteForm->createView(),
            );
        }

        $packLimitState = $this->container->get('civix_core.package_handler')
            ->getPackageStateForInvites($this->getUser());

        if ($packLimitState->isAllowedWith(count($emails))) {
            $invites = $this->container->get('civix_core.invite_sender')
                    ->saveInvites($emails, $this->getUser());

            $this->container->get('civix_core.invite_sender')->send($invites, $this->getUser());

            $this->get('session')->getFlashBag()->add('success', 'Invites have been successfully sent');
        } else {
            $this->get('session')->getFlashBag()->add('danger', 'Invites\' limit has been exceeded');
        }

        return $this->redirect($this->generateUrl('civix_front_group_invite'));
    }
}
