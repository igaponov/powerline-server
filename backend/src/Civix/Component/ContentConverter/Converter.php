<?php

namespace Civix\Component\ContentConverter;

use Civix\Component\ContentConverter\Source\ContentSourceInterface;

class Converter implements ConverterInterface
{
    /**
     * @var ContentSourceInterface[]
     */
    private $sources;

    public function __construct(ContentSourceInterface ...$sources)
    {
        $this->sources = $sources;
    }

    public function convert($content)
    {
        foreach ($this->sources as $source) {
            if ($source->isSupported($content)) {
                return $source->convert($content);
            }
        }

        return null;
    }
}