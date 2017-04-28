<?php

namespace Civix\Component\ContentConverter\Source;

class Base64 implements ContentSourceInterface
{
    public function isSupported($content)
    {
        if (!is_string($content)) {
            return false;
        }

        return base64_encode(base64_decode($content)) === $content;
    }

    public function convert($content)
    {
        return base64_decode($content);
    }
}