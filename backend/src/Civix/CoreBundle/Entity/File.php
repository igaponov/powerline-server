<?php

namespace Civix\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File as BaseFile;

/**
 * Class File
 * @package Civix\CoreBundle\Entity
 *
 * @ORM\Embeddable()
 */
class File
{
    /**
     * @var string
     *
     * @ORM\Column(name="name", nullable=true)
     */
    private $name;
    /**
     * @var BaseFile
     */
    private $file;

    public function __construct(?BaseFile $file = null)
    {
        $this->file = $file;
    }

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return File
     */
    public function setName(?string $name): File
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return BaseFile
     */
    public function getFile(): ?BaseFile
    {
        return $this->file;
    }

    /**
     * @param BaseFile $file
     * @return File
     */
    public function setFile(BaseFile $file): File
    {
        $this->file = $file;

        return $this;
    }
}