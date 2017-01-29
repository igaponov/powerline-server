<?php

namespace Civix\FrontBundle\Controller;

use Civix\CoreBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use JMS\DiExtraBundle\Annotation as DI;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/admin")
 */
class UserController extends Controller
{
    /**
     * @var EntityManager
     * @DI\Inject("doctrine.orm.entity_manager")
     */
    private $em;

    /**
     * @Route("/users", name="civix_front_user_index")
     * @Method({"GET"})
     * @Template("CivixFrontBundle:User:index.html.twig")
     * @param Request $request
     * @return array
     */
    public function indexAction(Request $request)
    {
        $query = $this->em
            ->getRepository('CivixCoreBundle:User')
            ->getQueryUserOrderedById();

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->get('page', 1),
            20
        );

        return [
            'pagination' => $pagination,
        ];
    }

    /**
     * @Route("/users/{id}/reset-password", name="civix_front_user_reset_password")
     * @Method({"GET"})
     * @param Request $request
     * @param User $user
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function resetPasswordAction(Request $request, User $user)
    {
        if ($this->isCsrfTokenValid('reset_user_password_'.$user->getId(), $request->get('_token'))) {
            $resetPasswordToken = base_convert(bin2hex(hash('sha256', uniqid(mt_rand(), true), true)), 16, 36);
            $user->setResetPasswordToken($resetPasswordToken);
            $user->setResetPasswordAt(new \DateTime());
            $this->em->persist($user);
            $this->em->flush();
            //send mail
            $this->get('civix_core.email_sender')
                ->sendResetPasswordEmail(
                    $user->getEmail(),
                    [
                        'name' => $user->getOfficialName(),
                        'link' => 'https://'.$this->getParameter('domain').'/#/reset-password/'.$resetPasswordToken,
                    ]
                );
            $this->addFlash('notice', 'The email has been sent to '.$user->getEmail());
        } else {
            $this->addFlash('error', 'Something went wrong');
        }

        return $this->redirectToRoute('civix_front_user_index');
    }

    /**
     * @Route("/user/{id}/delete", name="civix_front_user_delete")
     * @Method({"POST"})
     * @param Request $request
     * @param User $user
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function removeAction(Request $request, User $user)
    {
        if ($this->isCsrfTokenValid('remove_user_'.$user->getId(), $request->get('_token'))) {
            $this->em
                ->getRepository('CivixCoreBundle:User')
                ->removeUser($user);

            $this->addFlash('notice', 'User was removed');
        } else {
            $this->addFlash('error', 'Something went wrong');
        }

        return $this->redirectToRoute('civix_front_user_index');
    }
}