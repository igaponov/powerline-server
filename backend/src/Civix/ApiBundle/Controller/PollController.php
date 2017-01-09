<?php

namespace Civix\ApiBundle\Controller;

use Civix\CoreBundle\Event\Poll\AnswerEvent;
use Civix\CoreBundle\Event\PollEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Civix\CoreBundle\Entity\Activities\Question;
use Civix\CoreBundle\Entity\Poll\Answer;
use Civix\CoreBundle\Entity\Poll\Option;
use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\Entity\Poll\Question\Group as GroupQuestion;
use Civix\CoreBundle\Entity\Poll;

/**
 * @Route("/poll")
 */
class PollController extends BaseController
{
	/**
     * Deprecated, use `POST /api/v2/groups/{group}/polls` instead
     *
	 * @Route("/question/new", name="civix_api_question_new")
	 * @Method("PUT")
	 * 
	 * @ParamConverter("question", class="\Civix\CoreBundle\Entity\Poll\Question\Group")
	 * 
	 * @ApiDoc(
     *     section="Polls",
	 *     description="Add a new poll question",
	 *     statusCodes={
	 *         200="Returns when all succesfully added",
	 *         400="Bad Request",
	 *         405="Method Not Allowed"
	 *     },
     *     deprecated=true
	 * )
	 */
	public function putQuestionNewAction()
	{
		// @ŧodo Pending to use $question param as doctrine converter
		// , \Civix\CoreBundle\Entity\Poll\Question\Group $question,  = NULL
		
		$manager = $this->getDoctrine()->getManager();

		// Todo pending to fetch all the question data via
		// $request->getContent() when the doctrine converter works fine
		
		/** @var Question $question_data */
		/*
		$question_data = $this->jmsDeserialization(
				$request->getContent(),
				'\Civix\CoreBundle\Entity\Poll\Question\Group',
				['api-poll']
				);
		*/
		
		// Create a empty object until the $question_data could be parsed
		$question = new GroupQuestion();
		$question->setUser($this->getUser());
		/* @todo
		$question->setTitle($question_data->getTitle());
		$question->setSubject($question_data->getSubject());
		$question->setExpireAt($question_data->getExpireAt());
		*/
		
		$this->validate($question, null, ['api-poll']);
		
		$manager->persist($question);
		$manager->flush();
		
		$response = new JsonResponse();
		$response->setContent(
				$this->jmsSerialization(
						$question,
						['api-poll']
						)
				);
	
		return $response;
	}

    /**
     * Get Question by ID.
     * Deprecated, use `GET /api/v2/polls/{id}` instead
     *
     * @Route("/question/{question_id}", requirements={"question_id"="\d+"}, name="api_question_get")
     * @Method("GET")
     * @ApiDoc(
     *     section="Polls",
     *     description="Get Question by ID",
     *     statusCodes={
     *         200="Returns question",
     *         400="Bad Request",
     *         401="Authorization required",
     *         404="Question not found",
     *         405="Method Not Allowed"
     *     },
     *     deprecated=true
     * )
     * @param Request $request
     * @return Response
     */
    public function questionGetAction(Request $request)
    {
        $id = $request->get('question_id');

        $entityManager = $this->getDoctrine()->getManager();
        /** @var $question Poll\Question */
        $question = $entityManager->getRepository('CivixCoreBundle:Poll\Question')->findAsUser($id, $this->getUser());
        if (!$question) {
            throw $this->createNotFoundException();
        }

        $response = new Response($this->jmsSerialization($question, array('api-poll')));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Get Questions by representative.
     *
     * @Route(
     *      "/question/representative/{id}",
     *      requirements={"id"="\d+"},
     *      name="api_question_get_by_representative"
     * )
     * @Method("GET")
     * @ParamConverter(
     *      "activities",
     *      class="CivixCoreBundle:Activity",
     *      options={"repository_method" = "getActivitiesByRepresentativeId"}
     * )
     * @ApiDoc(
     *     section="Polls",
     *     description="Get Questions by representative",
     *     statusCodes={
     *         200="Returns questions",
     *         400="Bad Request",
     *         401="Authorization required",
     *         404="Question not found",
     *         405="Method Not Allowed"
     *     }
     * )
     * @param $activities
     * @return Response
     */
    public function questionGetByRepresentativeAction($activities)
    {
        $response = new Response($this->jmsSerialization($activities, array('api-activities')));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Get Questions by group.
     * Deprecated, use `GET /api/v2/groups/{group}/polls` instead
     *
     * @Route("/question/group/{id}", requirements={"id"="\d+"}, name="api_question_get_by_group")
     * @Method("GET")
     * @ParamConverter(
     *      "activities",
     *      class="CivixCoreBundle:Activity",
     *      options={"repository_method" = "getActivitiesByGroupId"}
     * )
     * @ApiDoc(
     *     section="Polls",
     *     description="Get Questions by group",
     *     statusCodes={
     *         200="Returns questions",
     *         400="Bad Request",
     *         401="Authorization required",
     *         404="Question not found",
     *         405="Method Not Allowed"
     *     },
     *     deprecated=true
     * )
     * @param $activities
     * @return Response
     */
    public function questionGetByGroupAction($activities)
    {
        $response = new Response($this->jmsSerialization($activities, array('api-activities')));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Get question answers.
     * Deprecated, use `GET /api/v2/polls/{id}/answers?followings=true` instead
     *
     * @Route(
     *      "/question/{question}/answers/influence",
     *      requirements={"question"="\d+"},
     *      name="api_question_answers_influence"
     * )
     * @ParamConverter("question", class="CivixCoreBundle:Poll\Question")
     * @Method("GET")
     * @ApiDoc(
     *     section="Polls",
     *     description="Get question answers",
     *     statusCodes={
     *         200="Returns answers",
     *         400="Bad Request",
     *         401="Authorization required",
     *         404="Question not found",
     *         405="Method Not Allowed"
     *     },
     *     deprecated=true
     * )
     * @param Request $request
     * @param Poll\Question $question
     * @return Response
     */
    public function answersByInfluenceAction(Request $request, Poll\Question $question)
    {
        $entityManager = $this->getDoctrine()->getManager();

        $answers = $entityManager
                ->getRepository('CivixCoreBundle:Poll\Answer')
                ->getAnswersByInfluence($this->getUser(), $question);

        $result = array(
            'answers' => $answers,
            'avatar_friend_hidden' => $request->getScheme()
                .'://'
                .$request->getHttpHost()
                .User::SOMEONE_AVATAR,
        );

        $response = new Response($this->jmsSerialization($result, array('api-answer', 'api-info')));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Get question answers.
     * Deprecated, use `GET /api/v2/polls/{id}/answers?followings=false` instead
     *
     * @Route(
     *      "/question/{question}/answers/influence/outside",
     *      requirements={"question"="\d+"},
     *      name="api_question_answers_influence_outside"
     * )
     * @ParamConverter("question", class="CivixCoreBundle:Poll\Question")
     * @Method("GET")
     * @ApiDoc(
     *     section="Polls",
     *     description="Get question answers",
     *     statusCodes={
     *         200="Returns answers",
     *         400="Bad Request",
     *         401="Authorization required",
     *         404="Question not found",
     *         405="Method Not Allowed"
     *     },
     *     deprecated=true
     * )
     * @param Request $request
     * @param Poll\Question $question
     * @return Response
     */
    public function answersByOutsideInfluenceAction(Request $request, Poll\Question $question)
    {
        $entityManager = $this->getDoctrine()->getManager();

        $answers = $entityManager
                ->getRepository('CivixCoreBundle:Poll\Answer')
                ->getAnswersByNotInfluence($this->getUser(), $question, 5);

        $result = array(
            'answers' => $answers,
            'avatar_someone' => $request->getScheme()
                .'://'
                .$request->getHttpHost()
                .User::SOMEONE_AVATAR,
        );

        $response = new Response($this->jmsSerialization($result, array('api-answer')));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Add new Answer.
     * Deprecated, use `PUT /api/v2/polls/{id}/answers/{option}` instead
     *
     * @Route("/question/{question_id}/answer/add", requirements={"question_id"="\d+"}, name="api_answer_add")
     * @Method("POST")
     *
     * @ApiDoc(
     *     section="Polls",
     *     deprecated=true
     * )
     * @param Request $request
     * @return Response
     */
    public function answerAddAction(Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $dispatcher = $this->get('event_dispatcher');

        /** @var $user User */
        $user = $this->getUser();
        /** @var $question Poll\Question */
        $question = $entityManager->getRepository('CivixCoreBundle:Poll\Question')->find($request->get('question_id'));
        if (is_null($question)) {
            throw new BadRequestHttpException('Question not found');
        }
        /** @var $option Option */
        $option = $entityManager->getRepository('CivixCoreBundle:Poll\Option')->find($request->get('option_id'));
        if (is_null($option) || $option->getQuestion()->getId() !== $question->getId()) {
            throw new BadRequestHttpException('Wrong option ID');
        }

        $isCanAnswer = $this->get('civix_core.poll.answer_manager')->checkAccessAnswer($user, $question);
        if (!$isCanAnswer) {
            throw new AccessDeniedHttpException();
        }

        /** @var $answer Answer */
        $answer = $entityManager->getRepository('CivixCoreBundle:Poll\Answer')
            ->findOneBy(array(
                'option' => $option,
                'user' => $user,
            ));
        if (!is_null($answer)) {
            throw new BadRequestHttpException('User is already answered this question');
        }

        $answer = new Answer();
        $answer->setQuestion($question);
        $answer->setOption($option);
        $answer->setUser($user);
        $answer->setComment($request->get('comment'));
        $answer->setPrivacy($request->get('privacy'));
        $answer->setPaymentAmount($request->get('payment_amount'));

        $errors = $this->getValidator()->validate($answer, null, array('api-poll'));

        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');

        if (count($errors) > 0) {
            $response->setStatusCode(400)
                ->setContent(json_encode(array('errors' => $this->transformErrors($errors))));
        } else {
            $entityManager->persist($answer);
            $entityManager->flush();

            $event = new AnswerEvent($answer);
            $dispatcher->dispatch(PollEvents::QUESTION_ANSWER, $event);

            $response->setContent($this->jmsSerialization($answer, array('api-answers-list')));
        }

        return $response;
    }

    /**
     * Add rate to comment.
     * Deprecated, use `POST /api/v2/poll-comments/{id}/rate` instead
     *
     * @Route(
     *      "/comments/rate/{id}/{action}",
     *      requirements={"id"="\d+", "action"="up|down|delete"},
     *      name="api_question_rate_comment"
     * )
     * @ParamConverter("comment", class="Civix\CoreBundle\Entity\Poll\Comment")
     * @Method("POST")
     * @ApiDoc(
     *     section="Polls",
     *     description="Add rate to comment",
     *     statusCodes={
     *         200="Returns comment with new rate",
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     },
     *     deprecated=true
     * )
     * @param Poll\Comment $comment
     * @param $action
     * @return Response
     */
    public function rateCommentAction(Poll\Comment $comment, $action)
    {
        $user = $this->getUser();
        $rateActionConstant = 'Civix\CoreBundle\Entity\Poll\CommentRate::RATE_'.strtoupper($action);

        if ($comment->getUser() == $user) {
            throw new BadRequestHttpException('You can\'t rate your comment');
        }

        $rate = $this->get('civix_core.comment_manager')
                ->updateRateToComment($comment, $user, constant($rateActionConstant));

        if ($comment instanceof Poll\Comment &&
            !$comment->getParentComment() &&
            $comment->getQuestion() instanceof Poll\Question\LeaderNews
        ) {
            $this->get('civix_core.activity_update')->updateEntityRateCount($rate);
        }

        $response = new Response($this->jmsSerialization($comment, array('api-comments', 'api-comments-parent')));

        return $response;
    }

    /**
     * Deprecated, use `GET /api/v2/user/poll-answers` instead.
     *
     * @Route("/answers/")
     * @Method("GET")
     *
     * @ApiDoc(
     *     section="Polls",
     *     deprecated=true
     * )
     */
    public function answersAction()
    {
        $answers = $this->getDoctrine()->getRepository(Answer::class)
            ->getFindByUserAndCriteriaQuery($this->getUser(), ['start' => new \DateTime('-35 days')])
            ->getResult();

        return new Response($this->jmsSerialization($answers, array('api-answers-list')));
    }
}
