<?php

namespace Civix\ApiBundle\Controller\V2_2\Group;

use Civix\ApiBundle\Form\Type\UserPetitionCreateType;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Service\UserPetitionManager;
use FOS\RestBundle\Controller\Annotations as REST;
use FOS\RestBundle\Controller\Annotations\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @Route("/groups/{group}/user-petitions")
 */
class UserPetitionController
{
    /**
     * @var UserPetitionManager
     */
    private $petitionManager;
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(
        UserPetitionManager $petitionManager,
        FormFactoryInterface $formFactory,
        TokenStorageInterface $tokenStorage
    ) {
        $this->petitionManager = $petitionManager;
        $this->formFactory = $formFactory;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Create a user's petition in a group
     *
     * @REST\Post("")
     *
     * @ApiDoc(
     *     authentication=true,
     *     resource=true,
     *     section="User Petitions",
     *     description="Create a user's petition in a group",
     *     input="Civix\ApiBundle\Form\Type\UserPetitionCreateType",
     *     statusCodes={
     *         400="Bad Request",
     *         405="Method Not Allowed"
     *     },
     *     responseMap={
     *          201 = {
     *              "class"="Civix\CoreBundle\Entity\UserPetition",
     *              "groups"={"petition"},
     *              "parsers" = {
     *                  "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *              }
     *          }
     *     }
     * )
     *
     * @View(serializerGroups={"petition"}, statusCode=201)
     *
     * @param Request $request
     * @param Group $group
     *
     * @return UserPetition|\Symfony\Component\Form\FormInterface
     */
    public function postAction(Request $request, Group $group)
    {
        $token = $this->tokenStorage->getToken();
        if (!$this->petitionManager->checkPetitionLimitPerMonth($token->getUser(), $group)) {
            throw new BadRequestHttpException('Your limit of petitions per month is reached.');
        }

        $form = $this->formFactory->create(UserPetitionCreateType::class);
        $form->submit($request->request->all());

        if ($form->isValid()) {
            /** @var UserPetition $petition */
            $petition = $form->getData();
            $petition->setUser($token->getUser());
            $petition->setGroup($group);
            $petition = $this->petitionManager->savePetition($petition);

            return $petition;
        }

        return $form;
    }
}
