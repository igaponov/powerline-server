<?php

namespace Civix\CoreBundle\Model;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class TempFile extends UploadedFile
{
    public function __construct($content = null)
    {
        $path = tempnam(sys_get_temp_dir(), 'temp_file_');
        if ($content) {
            file_put_contents($path, $content);
        }
        parent::__construct($path, uniqid('temp_file', true), null, null, null, true);
    }
}