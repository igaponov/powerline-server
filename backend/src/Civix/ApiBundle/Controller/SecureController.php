<?php

namespace Civix\ApiBundle\Controller;

use Civix\ApiBundle\Form\Type\UserFacebookRegistrationType;
use Civix\ApiBundle\Form\Type\UserRegistrationType;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Service\User\UserManager;
use FOS\RestBundle\Controller\Annotations\View;
use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @Route("/secure")
 */
class SecureController extends BaseController
{
    /**
     * @var UserManager
     * @DI\Inject("civix_core.user_manager")
     */
    private $manager;

    /**
     * Login a entity (User or SuperUser)
     * 
     * Example:
     *
     *     curl -i -X POST -H 'application/x-www-form-urlencoded' -G 'http://domain.com/api/secure/login' -d 'username=admin&password=admin'
     *
     * **Input Parameters**
     *
     *     username: the nick for the entity
     * 	   password: the password for the entity
     *
     * **Output Format**
     *
     * If successful:
     *
     *     {"token":"sometoken"}
     *
     * If error:
     *
     *     ["error","some error message"]
     *     
     * @Route("/login", name="api_secure_login")
     * @Method("POST")
     *
     * @ApiDoc(
     * 	   https = true,
     *     authentication = false,
     *     resource=true,
     *     section="Security",
     *     description="Login",
     *     views = { "default"},
     *     output = "",
     *     requirements={
	 *     },
     *     tags={
	 *         "stable" = "#89BF04",
	 *         "POST" = "#10a54a",
	 *         "login",
	 *     },
     *     filters={
     *         {"name"="username", "dataType"="string"},
     *         {"name"="password", "dataType"="string"}
     *     },
     *     parameters={
	 *     },
     *     input = {
	 *   	"class" = "",
	 *	    "options" = {"method" = "POST"},
	 *	   },
     *     statusCodes={
     *          200="Returned when successful with authorization token",
     *          400="Returned when incorrect login or password",
     *          405="Method Not Allowed"
     *     }
     * )
     */
    public function indexAction()
    {
        throw new \RuntimeException('You must configure the check path to be handled by the firewall using form_login in your security firewall configuration.');
    }

    /**
     * Deprecated, use `GET /api/v2/security/facebook` instead.
     *
     * @Route("/facebook/login", name="api_secure_facebook_login")
     * @Method("POST")
     *
     * @ApiDoc(
     *     section="Security",
     *     description="Facebook login",
     *     filters={
     *         {"name"="facebook_token", "dataType"="string"},
     *         {"name"="facebook_id", "dataType"="string"}
     *     },
     *     statusCodes={
     *         200="Returns authorization token",
     *         201="Need to register",
     *         400="Incorrect facebook token",
     *         405="Method Not Allowed"
     *     },
     *     deprecated=true
     * )
     * @param Request $request
     * @return Response
     */
    public function facebookLogin(Request $request)
    {
        $isTokenCorrect = $this->get('civix_core.facebook_api')->checkFacebookToken(
            $request->get('facebook_token'),
            $request->get('facebook_id')
        );

        if (!$isTokenCorrect) {
            throw new HttpException(400);
        }

        $user = $this->getDoctrine()->getManager()
                ->getRepository('CivixCoreBundle:User')
                ->getUserByFacebookId($request->get('facebook_id'));

        if ($user instanceof User) {
            $user->generateToken();
            $user->setFacebookToken($request->get('facebook_token'));
            $this->getDoctrine()->getManager()->flush();

            $response = new Response($this->jmsSerialization($user, array('api-session')));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }

        throw new HttpException(302);
    }

    /**
     * @Route("/registration")
     * @Method("POST")
     *
     * @ApiDoc(
     *      resource=true,
     *      section="Security",
     *      description="Registration",
     *      input="Civix\ApiBundle\Form\Type\UserRegistrationType",
     *      output={
     *           "class" = "Civix\CoreBundle\Entity\User",
     *           "groups" = {"api-session"},
     *           "parsers" = {
     *               "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *           }
     *      },
     *      statusCodes={
     *          200="Returns authorization token",
     *          400="Bad Request",
     *          405="Method Not Allowed"
     *      }
     * )
     *
     * @View(serializerGroups={"api-session"})
     *
     * @param Request $request
     * @return User|Response
     */
    public function registrationAction(Request $request)
    {
        $form = $this->createForm(UserRegistrationType::class);
        $form->submit($request->request->all());

        if ($form->isValid()) {
            return $this->manager->register($form->getData());
        }

        return $this->getBadRequestResponse($form);
    }

    /**
     * Deprecated, use `GET /api/v2/security/facebook` instead.
     *
     * @Route("/registration-facebook")
     * @Method("POST")
     * @ApiDoc(
     *     section="Security",
     *     section="Security",
     *     description="Registration from facebook",
     *     input="Civix\ApiBundle\Form\Type\UserFacebookRegistrationType",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\User",
     *          "groups" = {"api-session"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         200="Returns authorization token",
     *         400="Incorrect facebook token",
     *         405="Method Not Allowed"
     *     },
     *     deprecated=true
     * )
     *
     * @View(serializerGroups={"api-session"})
     *
     * @param Request $request
     * @return User|Response
     */
    public function facebookRegistration(Request $request)
    {
        $form = $this->createForm(UserFacebookRegistrationType::class);
        $form->submit($request->request->all());

        if ($form->isValid()) {
            return $this->manager->register($form->getData());
        }

        return $this->getBadRequestResponse($form);
    }

    /**
     * @Route("/forgot-password", name="api_secure_forgot_password")
     * @Method("POST")
     * @ApiDoc(
     *     resource=true,
     *     section="Security",
     *     description="Forgot password",
     *     filters={
     *         {"name"="email", "dataType"="string"},
     *     },
     *     statusCodes={
     *         200="Returns success",
     *         404="Email is not found",
     *         405="Method Not Allowed"
     *     }
     * )
     * @param Request $request
     * @return Response
     */
    public function forgotPassword(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('CivixCoreBundle:User')->findOneBy(array(
            'email' => $request->get('email'),
        ));
        if (!$user) {
            throw new HttpException(404);
        }

        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');

        //check reset expiration
        if (!$this->get('civix_core.user_manager')->checkResetInterval($user)) {
            $response->setStatusCode(400)->setContent(json_encode(array('errors' => array(array(
                    'message' => 'The password for this user has already been requested within the last 24 hours.',
                    ),
                )))
            );

            return $response;
        }

        //Generate reset token, set date of reset and sent email
        $resetPasswordToken = base_convert(bin2hex(hash('sha256', uniqid(mt_rand(), true), true)), 16, 36);
        $user->setResetPasswordToken($resetPasswordToken);
        $user->setResetPasswordAt(new \DateTime());
        $em->persist($user);
        $em->flush();

        //send mail
        $this->get('civix_core.email_sender')->sendResetPasswordEmail(
            $user->getEmail(),
            array(
                'name' => $user->getOfficialName(),
                'link' => $request->getScheme().'://'.str_replace('api.', '', $request->getHttpHost()).'/#/reset-password/'.$resetPasswordToken,
            )
        );
        $response->setContent(json_encode(array('status' => 'ok')))->setStatusCode(200);

        return $response;
    }

    /**
     * @Route("/resettoken/{token}", name="api_secure_check_token")
     * @Method("GET")
     * @ApiDoc(
     *     resource=true,
     *     section="Security",
     *     description="Check reset token",
     *     filters={
     *         {"name"="token", "dataType"="string"},
     *     },
     *     statusCodes={
     *         200="Returns success",
     *         404="User is not found",
     *         405="Method Not Allowed"
     *     }
     * )
     * @param Request $request
     * @return Response
     */
    public function checkResetToken(Request $request)
    {
        $this->getUserByResetToken($request->get('token'));

        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent(json_encode(array('status' => 'ok')))->setStatusCode(200);

        return $response;
    }

    /**
     * @Route("/resettoken/{token}", name="api_secure_password_update")
     * @Method("POST")
     * @ApiDoc(
     *     resource=true,
     *     section="Security",
     *     description="Check reset token",
     *     filters={
     *         {"name"="token", "dataType"="string"},
     *         {"name"="password", "dataType"="string"},
     *         {"name"="passwordConfirm", "dataType"="string"}
     *     },
     *     statusCodes={
     *         200="Returns success",
     *         404="User is not found (token incorrect)",
     *         405="Method Not Allowed"
     *     }
     * )
     * @param Request $request
     * @return Response
     */
    public function saveNewPassword(Request $request)
    {
        $user = $this->getUserByResetToken($request->get('token'));

        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');

        $changePasswordModel = $this->jmsDeserialization(
            $request->getContent(),
            'Civix\CoreBundle\Model\User\ChangePassword',
            array('Default')
        );
        $errors = $this->getValidator()->validate($changePasswordModel);
        if (count($errors) > 0) {
            $response->setStatusCode(400)->setContent(json_encode(array('errors' => $this->transformErrors($errors))));

            return $response;
        } else {
            $em = $this->getDoctrine()->getManager();
            $encoder = $this->get('security.encoder_factory')->getEncoder($user);
            $password = $encoder->encodePassword($changePasswordModel->getPassword(), $user->getSalt());
            $user->setPassword($password);
            $user->setResetPasswordToken(null);
            $user->setResetPasswordAt(null);
            $em->persist($user);
            $em->flush();

            $response->setContent(json_encode(array('status' => 'ok')))->setStatusCode(200);

            return $response;
        }
    }

    private function getUserByResetToken($token)
    {
        if (empty($token)) {
            throw new HttpException(404);
        }
        $user = $this->getDoctrine()->getManager()->getRepository('CivixCoreBundle:User')->findOneBy(array(
            'resetPasswordToken' => $token,
        ));
        if (!$user) {
            throw new HttpException(404);
        }

        return $user;
    }

    /**
     * @param $form
     * @return Response
     */
    private function getBadRequestResponse(Form $form): Response
    {
        $errors = [];
        foreach ($form as $name => $element) {
            $error = $element->getErrors()
                ->current();
            if ($error) {
                $errors[] = [
                    'property' => $name,
                    'message' => $error->getMessage(),
                ];
            }
        }
        foreach ($form->getErrors() as $error) {
            $errors[] = [
                'property' => null,
                'message' => $error->getMessage(),
            ];
        }

        return new Response(json_encode(['errors' => $errors]), 400);
    }
}
