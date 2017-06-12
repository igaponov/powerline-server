<?php

namespace Civix\CoreBundle\Entity;

interface CheckingLimits
{
    public function getQuestionLimit(): ?int;

    public function setQuestionLimit(?int $limit);
}
