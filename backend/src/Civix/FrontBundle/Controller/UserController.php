<?php

namespace Civix\FrontBundle\Controller;

use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\UserPetition;
use Doctrine\ORM\EntityManager;
use JMS\DiExtraBundle\Annotation as DI;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @Route("/admin/users")
 */
class UserController extends Controller
{
    /**
     * @var EntityManager
     * @DI\Inject("doctrine.orm.entity_manager")
     */
    private $em;

    /**
     * @Route("", name="civix_front_user_index")
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
     * @Route("/{id}/reset-password", name="civix_front_user_reset_password")
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
     * @Route("/{id}/ban", name="civix_front_user_ban")
     * @Method({"POST"})
     * @param Request $request
     * @param User $user
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function banAction(Request $request, User $user)
    {
        if ($this->isCsrfTokenValid('user_ban', $request->get('_token'))) {
            $user->disable();
            $this->em->persist($user);
            $this->em->flush();

            $this->addFlash('notice', 'User was banned');
        } else {
            $this->addFlash('error', 'Something went wrong');
        }

        return $this->redirect($request->headers->get('Referer'));
    }

    /**
     * @Route("/{id}/posts", name="civix_front_user_posts")
     * @Method({"GET"})
     * @Template("CivixFrontBundle:Post:index.html.twig")
     * @param Request $request
     * @param $id
     * @return array
     */
    public function getUserPostsAction(Request $request, $id)
    {
        $query = $this->em->getRepository(Post::class)
            ->getFindByQuery(['user' => $id]);

        $pagination = $this->get('knp_paginator')->paginate(
            $query,
            $request->get('page', 1),
            20
        );

        return compact('pagination');
    }

    /**
     * @Route("/{id}/petitions", name="civix_front_user_petitions")
     * @Method({"GET"})
     * @Template("CivixFrontBundle:Petition:index.html.twig")
     * @param Request $request
     * @param User $user
     * @return array
     */
    public function getUserPetitionsAction(Request $request, User $user)
    {
        $query = $this->em->getRepository(UserPetition::class)
            ->getFindByQuery(['user' => $user]);

        $pagination = $this->get('knp_paginator')->paginate(
            $query,
            $request->get('page', 1),
            20
        );

        return compact('pagination');
    }
}