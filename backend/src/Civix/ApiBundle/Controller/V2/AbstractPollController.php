<?php

namespace Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Form\Type\Poll\CreateQuestionType;
use Civix\CoreBundle\Entity\LeaderContentRootInterface;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Service\PollManager;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractPollController extends FOSRestController
{
    /**
     * @return PollManager
     */
    abstract protected function getManager();

    protected function getPolls(ParamFetcher $params, LeaderContentRootInterface $root)
    {
        /** @var $query \Doctrine\ORM\Query */
        $query = $this->getDoctrine()->getRepository('CivixCoreBundle:Poll\Question')
            ->getFilteredQuestionQuery(
                $params->get('filter'),
                $root
            );

        $paginator = $this->get('knp_paginator');
        return $paginator->paginate(
            $query,
            $params->get('page'),
            $params->get('per_page')
        );
    }

    protected function postPoll(Request $request, LeaderContentRootInterface $root)
    {
        $form = $this->createForm(CreateQuestionType::class, null, ['root_model' => $root]);
        $form->submit($request->request->all());

        if ($form->isValid()) {
            /** @var Question $question */
            $question = $form->getData();
            $question->setUser($this->getUser());
            $question->setOwner($root);

            return $this->getManager()->savePoll($question);
        }

        return $form;
    }
}
