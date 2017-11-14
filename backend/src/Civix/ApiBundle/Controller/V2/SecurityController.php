<?php
namespace Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Form\Type\RegistrationType;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Service\User\UserManager;
use FOS\RestBundle\Controller\Annotations as REST;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/security")
 */
class SecurityController
{
    /**
     * @var UserManager
     */
    private $manager;
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    public function __construct(UserManager $manager, FormFactoryInterface $formFactory)
    {
        $this->manager = $manager;
        $this->formFactory = $formFactory;
    }

    /**
     * Checks user credentials from facebook,
     * creates user if it's not in db, issues authentication token on success
     *
     * @REST\Get("/facebook")
     *
     * @QueryParam(name="code", nullable=false, allowBlank=false, strict=true)
     *
     * @ApiDoc(
     *     resource=true,
     *     section="Security",
     *     description="Checks user credentials from facebook",
     *     output = {
     *          "class" = "Civix\CoreBundle\Entity\User",
     *          "groups" = {"api-session"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         400="Bad Request",
     *         405="Method Not Allowed"
     *     }
     * )
     */
    public function facebookAction(): void
    {
        throw new \RuntimeException(
            'You must configure the check path to be handled by the firewall using oauth in your security firewall configuration.'
        );
    }

    /**
     * @REST\Post("/registration")
     *
     * @ApiDoc(
     *      resource=true,
     *      section="Security",
     *      description="Registration",
     *      input="Civix\ApiBundle\Form\Type\RegistrationType",
     *      output={
     *           "class" = "Civix\CoreBundle\Entity\User",
     *           "groups" = {"api-session"},
     *           "parsers" = {
     *               "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *           }
     *      },
     *      statusCodes={
     *          400="Bad Request",
     *      }
     * )
     *
     * @REST\View(serializerGroups={"api-session"})
     *
     * @param Request $request
     * @return User|FormInterface
     */
    public function registrationAction(Request $request)
    {
        $form = $this->formFactory->create(RegistrationType::class);
        $form->handleRequest($request->request->all());

        if ($form->isValid()) {
            return $this->manager->register($form->getData());
        }

        return $form;
    }

    /**
     * Login a User
     * Send only a `phone` parameter to start a verification,
     * the server will return response "200 ok" on successful.
     * Send both `phone` and `code` parameters to check the verification,
     * the server will return a `token`.
     *
     * @REST\Post("/login")
     *
     * @QueryParam(name="phone", nullable=false, allowBlank=false, strict=true)
     * @QueryParam(name="code", nullable=true, allowBlank=true, strict=true)
     *
     * @ApiDoc(
     *     resource=true,
     *     section="Security",
     *     description="Login",
     *     output = {
     *          "class" = "Civix\CoreBundle\Entity\User",
     *          "groups" = {"api-session"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         400="Bad Request",
     *         405="Method Not Allowed"
     *     }
     * )
     */
    public function loginAction(): void
    {
        throw new \RuntimeException(
            'You must configure the check path to be handled by the firewall using phone-form in your security firewall configuration.'
        );
    }
}