<?php
namespace Civix\CoreBundle\Storage;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Storage\AbstractStorage;

class VichArrayStorage extends AbstractStorage
{
    private $files = [];

    protected function doUpload(PropertyMapping $mapping, UploadedFile $file, $dir, $name)
    {
        if (!isset($this->files[$mapping->getUploadDestination()])) {
            $this->files[$mapping->getUploadDestination()] = [];
        }
        $this->files[$mapping->getUploadDestination()][$name] = $file;
    }

    protected function doRemove(PropertyMapping $mapping, $dir, $name)
    {
        unset($this->files[$mapping->getUploadDestination()][$name]);
    }

    protected function doResolvePath(PropertyMapping $mapping, $dir, $name, $relative = false)
    {
        return "[{$mapping->getUploadDestination()}][$name]";
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
        $mapping = new PropertyMapping(null, null);
        $mapping->setMapping(['upload_destination' => $dir]);
        $this->doUpload($mapping, $file, '', $name);
    }
}