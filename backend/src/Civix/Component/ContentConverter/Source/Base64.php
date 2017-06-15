<?php

namespace Civix\Component\ContentConverter\Source;

class Base64 implements ContentSourceInterface
{
    public function isSupported(string $content): bool
    {
        if (!is_string($content)) {
            return false;
        }

        return base64_encode(base64_decode($content)) === $content;
    }

    public function convert(string $content)
    {
        return base64_decode($content);
    }
}