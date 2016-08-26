<?php

namespace Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Service\User\UserManager;
use FOS\RestBundle\Controller\FOSRestController;
use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route("/user/polls")
 */
class UserPollsController extends FOSRestController
{
    /**
     * @var UserManager
     * @DI\Inject("civix_core.user_manager")
     */
    private $manager;

    /**
     * @Route("/{id}", requirements={"id"="\d+"})
     * @Method("PUT")
     *
     * @SecureParam("poll", permission="subscribe")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Polls",
     *     description="Subscribe to a poll",
     *     requirements={
     *         {"name"="id", "dataType"="integer", "description"="Poll id"}
     *     },
     *     statusCodes={
     *         204="Success",
     *         404="Poll Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param Question $poll
     */
    public function putAction(Question $poll)
    {
        $this->manager->subscribeToPoll($this->getUser(), $poll);
    }

    /**
     * @Route("/{id}", requirements={"id"="\d+"})
     * @Method("DELETE")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Polls",
     *     description="Unsubscribe from a poll",
     *     requirements={
     *         {"name"="id", "dataType"="integer", "description"="Poll id"}
     *     },
     *     statusCodes={
     *         204="Success",
     *         404="Poll Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param Question $poll
     */
    public function deleteAction(Question $poll)
    {
        $this->manager->unsubscribeFromPoll($this->getUser(), $poll);
    }
}
