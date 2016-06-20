<?php

namespace Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Form\Type\Micropetitions\PetitionCreateType;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Micropetitions\Petition;
use Civix\CoreBundle\Service\Micropetitions\PetitionManager;
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
 * Class MicropetitionController
 * @package Civix\ApiBundle\Controller\V2
 *
 * @Route("/groups")
 */
class GroupMicropetitionController extends FOSRestController
{
    /**
     * @var PetitionManager
     * @DI\Inject("civix_core.poll.micropetition_manager")
     */
    private $petitionManager;

    /**
     * Create a micropetition in a group
     * 
     * @Route("/{id}/micro-petitions")
     * @Method("POST")
     *
     * @ParamConverter("group")
     *
     * @ApiDoc(
     *     authentication=true,
     *     resource=true,
     *     section="Micropetitions",
     *     description="Create a micropetition in a group",
     *     input="Civix\ApiBundle\Form\Type\Micropetitions\PetitionCreateType",
     *     output={
     *         "class"="Civix\CoreBundle\Entity\Micropetitions\Petition",
     *         "groups"={"api-petitions-create"},
     *         "parsers" = {
     *             "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *         }
     *     },
     *     statusCodes={
     *         201="Returns new micropetition",
     *         400="Bad Request",
     *         405="Method Not Allowed"
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
        $form = $this->createForm(new PetitionCreateType(), null, ['validation_groups' => 'create']);
        $form->submit($request);

        // check limit petition
        if (!$this->petitionManager->checkPetitionLimitPerMonth($this->getUser(), $group)) {
            $form->addError(new FormError('Your limit of petitions per month is reached.'));
        }

        if ($form->isValid()) {
            /** @var Petition $petition */
            $petition = $form->getData();
            $petition->setUser($this->getUser());
            $petition->setGroup($group);
            $petition->setGroupId($group->getId());
            $petition = $this->petitionManager->savePetition($petition);

            return $this->view($petition, Codes::HTTP_CREATED);
        }
        
        return $form;
    }
}
