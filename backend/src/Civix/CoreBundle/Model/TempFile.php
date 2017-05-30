<?php

namespace Civix\CoreBundle\Model;

use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesser;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class TempFile extends UploadedFile
{
    public function __construct($content)
    {
        $path = tempnam(sys_get_temp_dir(), 'temp_file_');
        if ($content) {
            file_put_contents($path, $content);
        }
        $mimeType = mime_content_type($path);
        $guesser = ExtensionGuesser::getInstance();
        $ext = $guesser->guess($mimeType);
        parent::__construct($path, uniqid('', false).'.'.$ext, $mimeType, mb_strlen($content), UPLOAD_ERR_OK, true);
    }
}