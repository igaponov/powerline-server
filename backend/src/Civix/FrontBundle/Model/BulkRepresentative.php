<?php

namespace Civix\FrontBundle\Model;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

class BulkRepresentative
{
    /**
     * @var UploadedFile
     *
     * @Assert\NotBlank()
     * @Assert\File(mimeTypes={"text/csv", "text/plain"})
     */
    private $file;

    /**
     * @return UploadedFile
     */
    public function getFile(): ?UploadedFile
    {
        return $this->file;
    }

    /**
     * @param UploadedFile $file
     * @return BulkRepresentative
     */
    public function setFile(UploadedFile $file): BulkRepresentative
    {
        $this->file = $file;

        return $this;
    }
}