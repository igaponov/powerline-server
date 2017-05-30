<?php

namespace Civix\Component\ContentConverter\Source;

interface ContentSourceInterface
{
    public function isSupported(string $content): bool;

    public function convert(string $content);
}