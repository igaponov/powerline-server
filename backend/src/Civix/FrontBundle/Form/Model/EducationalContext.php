<?php

namespace Civix\FrontBundle\Form\Model;

use Civix\CoreBundle\Entity\Poll\Question;
use Symfony\Component\Validator\Constraints as Assert;

class EducationalContext
{
    /**
     * @var Question
     */
    private $question;

    /**
     * @Assert\Valid()
     */
    private $items = array();

    public function __construct(Question $question = null)
    {
        $this->question = $question;

        foreach ($question->getEducationalContext() as $context) {
            $this->items[] = new EducationalItem($context);
        }
    }

    public function getItems()
    {
        return $this->items;
    }

    public function setItems($items)
    {
        $this->items = $items;
    }
}
