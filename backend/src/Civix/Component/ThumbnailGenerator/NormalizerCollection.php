<?php

namespace Civix\Component\ThumbnailGenerator;

class NormalizerCollection implements \IteratorAggregate
{
    /**
     * @var ObjectNormalizerInterface[]
     */
    private $normalizers;

    public function __construct(ObjectNormalizerInterface ...$normalizers)
    {
        $this->normalizers = $normalizers;
    }

    /**
     * @return \ArrayIterator|ObjectNormalizerInterface[]
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->normalizers);
    }
}