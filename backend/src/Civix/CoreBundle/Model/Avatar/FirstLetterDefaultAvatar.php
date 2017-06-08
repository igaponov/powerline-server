<?php

namespace Civix\CoreBundle\Model\Avatar;

class FirstLetterDefaultAvatar implements DefaultAvatarInterface
{
    const NUM_TO_LETTER = [
        0 => 'Z',
        1 => 'O',
        2 => 'T',
        3 => 'T',
        4 => 'F',
        5 => 'F',
        6 => 'S',
        7 => 'S',
        8 => 'E',
        9 => 'N',
    ];

    /**
     * @var string
     */
    private $letter;

    public function __construct(string $firstName)
    {
        if ($firstName === '') {
            throw new \LogicException('The first name for avatar should have at least one letter.');
        }
        $this->letter = mb_strtoupper($firstName[0]);
        if (is_numeric($this->letter)) {
            $this->letter = self::NUM_TO_LETTER[$this->letter];
        }
    }

    /**
     * @return string
     */
    public function getLetter(): string
    {
        return $this->letter;
    }
}