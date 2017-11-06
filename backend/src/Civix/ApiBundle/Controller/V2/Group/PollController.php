<?php

namespace Civix\ApiBundle\Controller\V2\Group;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\ApiBundle\Controller\V2\AbstractPollController;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Service\PollManager;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Request\ParamFetcher;
use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class for Leader Polls controller
 * 
 * @Route("/groups/{group}/polls")
 */
class PollController extends AbstractPollController
{
    /**
     * @var PollManager
     * @DI\Inject("civix_core.poll_manager")
     */
    private $manager;

    protected function getManager()
    {
        return $this->manager;
    }

    /**
     * List all the polls and questions based in the current user type.
     *
     * @Route("")
     * @Method("GET")
     *
     * @SecureParam("group", permission="view")
     *
     * @QueryParam(name="filter", requirements="published|unpublished|publishing|archived", description="Filter by question state")
     * @QueryParam(name="page", requirements="\d+", default=1)
     * @QueryParam(name="per_page", requirements="(10|20)", default="20")
     *
     * @ApiDoc(
     *     authentication=true,
     *     resource=true,
     *     section="Polls",
     *     description="List all the polls and questions based in the current user type.",
     *     output="Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination",
     *     statusCodes={
     *         200="Returns list",
     *         403="Access Denied",
     *         404="Group Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"paginator", "api-poll"})
     *
     * @param ParamFetcher $params
     * @param Group $group
     *
     * @return \Knp\Component\Pager\Pagination\PaginationInterface
     */
    public function getPollsAction(ParamFetcher $params, Group $group)
    {
        return $this->getPolls($params, $group);
    }

    /**
     * Add poll
     *
     * @Route("")
     * @Method("POST")
     *
     * @SecureParam("group", permission="content")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Polls",
     *     description="Add poll",
     *     input="Civix\ApiBundle\Form\Type\Poll\CreateQuestionType",
     *     output={
     *          "class" = "Civix\CoreBundle\Entity\Poll\Question",
     *          "groups" = {"api-poll"}
     *     },
     *     statusCodes={
     *         201="Success",
     *         400="Bad Request",
     *         403="Access Denied",
     *         404="Group Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api-poll"})
     *
     * @param Request $request
     * @param Group $group
     *
     * @return Question|\Symfony\Component\Form\Form
     */
    public function postPollAction(Request $request, Group $group)
    {
        return $this->postPoll($request, $group);
    }
}
