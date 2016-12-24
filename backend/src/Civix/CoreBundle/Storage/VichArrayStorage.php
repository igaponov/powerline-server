<?php
namespace Civix\CoreBundle\Storage;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Storage\AbstractStorage;

class VichArrayStorage extends AbstractStorage
{
    private $files = [];

    protected function doUpload(UploadedFile $file, $dir, $name)
    {
        if (!isset($this->files[$dir])) {
            $this->files[$dir] = [];
        }
        $this->files[$dir][$name] = $file;
    }

    protected function doRemove($dir, $name)
    {
        unset($this->files[$dir][$name]);
    }

    protected function doResolvePath($dir, $name)
    {
        return "[$dir][$name]";
    }

    public function getFiles($dir)
    {
        if (isset($this->files[$dir])) {
            return $this->files[$dir];
        }

        return [];
    }

    public function addFile(UploadedFile $file, $dir, $name)
    {
        $this->doUpload($file, $dir, $name);
    }
}