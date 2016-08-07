<?php
namespace Civix\CoreBundle\Entity\Poll;

interface ContentInterface
{
    /**
     * @return Question
     */
    public function getQuestion();
}