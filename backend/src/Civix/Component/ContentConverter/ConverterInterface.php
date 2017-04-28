<?php

namespace Civix\Component\ContentConverter;

interface ConverterInterface
{
    /**
     * @param mixed $content
     * @return string|null
     */
    public function convert($content);
}