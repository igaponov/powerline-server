<?php

namespace Civix\CoreBundle\Model\Avatar;

class FirstLetterDefaultAvatar implements DefaultAvatarInterface
{
    /**
     * @var string
     */
    private $letter;

    public function __construct(string $firstName)
    {
        if (mb_strlen($firstName) === 0) {
            throw new \LogicException('The first name for avatar should have at least one letter.');
        }
        $this->letter = substr($firstName, 0, 1);
    }

    /**
     * @return string
     */
    public function getLetter(): string
    {
        return $this->letter;
    }
}