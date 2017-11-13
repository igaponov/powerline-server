<?php

namespace Civix\Component\ThumbnailGenerator;

interface ObjectNormalizerInterface
{
    public function supports($object): bool;

    public function normalize($object);
}