<?php

namespace Civix\ApiBundle\Controller\Leader;

use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Civix\ApiBundle\Controller\BaseController;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\Poll\Answer;

/**
 * @Route("/polls")
 */
class PollController extends BaseController
{
    /**
     * @Route("/")
     * @Method("GET")
     * 
     * @ApiDoc(
     *     resource=true,
     *     description="List a Leader Poll",
     *     statusCodes={
     *         200="Returns list",
     *         400="Bad Request",
     *         401="Authorization required",
     *         404="Question not found",
     *         405="Method Not Allowed"
     *     }
     * )
     */
    public function listAction(Request $request)
    {
        $polls = [];
        if ($request->query->has('mine')) {
            $polls = $this->getDoctrine()
                ->getRepository($this->getEntityRepository($request->get('type'), ucfirst($this->getUser()->getType())))
                ->findBy(['user' => $this->getUser()], ['id' => 'DESC']);
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
        if ($question->getUser() !== $this->getUser()) {
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
