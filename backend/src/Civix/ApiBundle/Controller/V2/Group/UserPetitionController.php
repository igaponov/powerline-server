<?php

namespace Civix\ApiBundle\Controller\V2\Group;

use Civix\ApiBundle\Form\Type\UserPetitionCreateType;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Micropetitions\Petition;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Service\UserPetitionManager;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Util\Codes;
use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/groups/{group}/user-petitions")
 */
class UserPetitionController extends FOSRestController
{
    /**
     * @var UserPetitionManager
     * @DI\Inject("civix_core.user_petition_manager")
     */
    private $petitionManager;

    /**
     * Create a user's petition in a group
     *
     * @Route("")
     * @Method("POST")
     *
     * @ParamConverter("group")
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
     *              "groups"={"api-petitions-create"},
     *              "parsers" = {
     *                  "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *              }
     *          }
     *     }
     * )
     *
     * @param Request $request
     * @param Group $group
     *
     * @return Petition|\Symfony\Component\Form\Form
     */
    public function postAction(Request $request, Group $group)
    {
        $form = $this->createForm(new UserPetitionCreateType(), null, ['validation_groups' => 'create']);
        $form->submit($request);

        // check limit petition
        if (!$this->petitionManager->checkPetitionLimitPerMonth($this->getUser(), $group)) {
            $form->addError(new FormError('Your limit of petitions per month is reached.'));
        }

        if ($form->isValid()) {
            /** @var UserPetition $petition */
            $petition = $form->getData();
            $petition->setUser($this->getUser());
            $petition->setGroup($group);
            $petition = $this->petitionManager->savePetition($petition);

            return $this->view($petition, Codes::HTTP_CREATED);
        }

        return $form;
    }
}
