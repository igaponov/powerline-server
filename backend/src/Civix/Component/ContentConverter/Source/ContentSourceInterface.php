<?php

namespace Civix\Component\ContentConverter\Source;

interface ContentSourceInterface
{
    public function isSupported($content);

    public function convert($content);
}