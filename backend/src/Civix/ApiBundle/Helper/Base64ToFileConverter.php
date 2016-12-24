<?php
namespace Civix\ApiBundle\Helper;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class Base64ToFileConverter
{
    public static function convert($content)
    {
        if (!empty($content)) {
            $content = base64_decode($content, true);
            $path = tempnam(sys_get_temp_dir(), 'upload');
            file_put_contents($path, $content);
            $content = new UploadedFile($path, $content, null, mb_strlen($content), null, true);
        }

        return $content;
    }
}