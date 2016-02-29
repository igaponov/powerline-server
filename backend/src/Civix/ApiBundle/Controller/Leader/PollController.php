<?php

namespace Civix\ApiBundle\Controller\Leader;

use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Civix\ApiBundle\Controller\BaseController;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\Poll\Answer;

/**
 * Class for Leader Polls controller
 * 
 * @Route("/polls")
 */
class PollController extends BaseController
{
    /**
     * List all the polls and questions based in the current user type.
     * 
     * Additionaly the polls could be filtered for show only for the current user.
     * 
     * @Route("/")
     * @Method("GET")
     * 
     * @ApiDoc(
     *     resource=true,
     *     description="List all the polls and questions based in the current user type.",
     *     statusCodes={
     *         200="Returns list",
     *         400="Bad Request",
     *         401="Authorization required",
     *         404="Question not found",
     *         405="Method Not Allowed"
     *     }
     * )
     * 
     * @return [object Poll]
     */
    public function listAction(Request $request)
    {
    	// @see $this->getEntityRepository()
    	$repositoryType = $request->get('type');
    	$currentUser = $this->getUser();
    	// Fetch the Question type base repository by type
    	$entityRepository = $this->getEntityRepository($repositoryType, ucfirst($currentUser->getType()));
    	
        $polls = [];
        // Filter only the current user polls
        if($request->query->has('mine')) 
        {
            $polls = $this->getDoctrine()->getRepository($entityRepository)
                		  ->findBy(['user' => $currentUser], ['id' => 'DESC']);
        }

        return $this->createJSONResponse($this->jmsSerialization($polls, ['api-leader-poll']));
    }

    /**
     * List all the answers for a given question.
     * 
     * The current user only can list the answers if it is the question user creator.
     * 
     * @Route("/{id}/answers/")
     * @Method("GET")
     * 
     * @ApiDoc(
     *     resource=true,
     *     description="List all the answers for a given question.",
     *     statusCodes={
     *         200="Returns list",
     *         400="Bad Request",
     *         401="Authorization required",
     *         404="Question not found",
     *         405="Method Not Allowed"
     *     }
     * )
     */
    public function answersListAction(Question $question)
    {
    	// Forbid list the answers if the current user is different for the question user
        if ($question->getUser() !== $this->getUser()) 
        {
            return $this->createJSONResponse('', 403);
        }
        
        // Fetch all the answers for the question selected
        $answers = $this->getDoctrine()->getRepository(Answer::class)->findByQuestion($question);

        return $this->createJSONResponse($this->jmsSerialization($answers, ['api-leader-answers']));
    }

    /**
     * Get the Poll Question classname entity (prefix) based in the repository type
     * 
     * @param string $type The repository type
     * @param string $prefix The base prefix for classname loading
     * @access private
     * 
     * @return string
     */
    private function getEntityRepository($type, $prefix)
    {
        switch($type)
        {
        	case 'petition':
        		$className = $prefix . 'Petition';
        	break;
        	case 'news':
        		$className = $prefix . 'News';
        	break;
        	case 'payment_request':
        		$className = $prefix . 'PaymentRequest';
        	break;
        	case 'event':
        		$className = $prefix . 'Event';
        	break;
        	default:
        		$className = $prefix;
        	break;
        }

        return 'Civix\\CoreBundle\\Entity\\Poll\\Question\\' . $className;
    }
}
