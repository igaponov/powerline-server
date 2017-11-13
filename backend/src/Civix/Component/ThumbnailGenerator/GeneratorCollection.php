<?php

namespace Civix\Component\ThumbnailGenerator;

class GeneratorCollection implements \IteratorAggregate
{
    /**
     * @var ObjectThumbnailGeneratorInterface[]
     */
    private $generators;

    public function __construct(ObjectThumbnailGeneratorInterface ...$generators)
    {
        $this->generators = $generators;
    }

    /**
     * @return \ArrayIterator|ObjectThumbnailGeneratorInterface[]
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->generators);
    }
}