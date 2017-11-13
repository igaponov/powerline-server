<?php

namespace Civix\Component\ThumbnailGenerator;

use Intervention\Image\Image;

class ThumbnailGenerator implements ThumbnailGeneratorInterface
{
    /**
     * @var NormalizerCollection
     */
    private $normalizerCollection;
    /**
     * @var GeneratorCollection
     */
    private $generatorCollection;

    public function __construct(NormalizerCollection $normalizerCollection, GeneratorCollection $generatorCollection)
    {
        $this->normalizerCollection = $normalizerCollection;
        $this->generatorCollection = $generatorCollection;
    }

    public function generate($object): Image
    {
        foreach ($this->normalizerCollection as $normalizer) {
            if ($normalizer->supports($object)) {
                $object = $normalizer->normalize($object);
            }
        }
        foreach ($this->generatorCollection as $converter) {
            if ($converter->supports($object)) {
                return $converter->generate($object);
            }
        }
        throw new \LogicException(sprintf('Generator for class %s is not found.', get_class($object)));
    }
}