<?php

namespace Civix\Component\ThumbnailGenerator;

interface ObjectThumbnailGeneratorInterface
{
    public function supports($object);

    public function generate($object);
}