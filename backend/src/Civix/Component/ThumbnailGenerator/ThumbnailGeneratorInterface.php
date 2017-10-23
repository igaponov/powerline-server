<?php

namespace Civix\Component\ThumbnailGenerator;

use Intervention\Image\Image;

interface ThumbnailGeneratorInterface
{
    public function generate($object): Image;
}