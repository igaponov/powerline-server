<?php

namespace Civix\CoreBundle\Request\ParamConverter\Comment;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Civix\CoreBundle\Model\Comment\CommentModelFactory;
use Civix\CoreBundle\Model\Comment\CommentModelInterface;

/**
 * @deprecated
 */
class CommentModelConverter implements ParamConverterInterface
{
    public function supports(ParamConverter $configuration)
    {
        if (!$configuration->getName()) {
            return false;
        }

        if (!$configuration->getClass()) {
            return false;
        }

        return $configuration->getClass() === 'Civix\CoreBundle\Model\Comment\CommentModelInterface';
    }

    public function apply(Request $request, ParamConverter $configuration)
    {
        $options = $configuration->getOptions();
        $typeEntity = $request->attributes->get($options['typeEntity']);

        $commentModel = CommentModelFactory::createByType($typeEntity);
        if (!($commentModel instanceof CommentModelInterface)) {
            throw new NotFoundHttpException('Not found');
        }

        $request->attributes->set($configuration->getName(), $commentModel);
    }
}
