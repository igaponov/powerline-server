<?php
namespace Civix\ApiBundle\Request\ParamConverter;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\DoctrineParamConverter as BaseDoctrineParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * Class DoctrineParamConverter
 * @package Civix\ApiBundle\Request\ParamConverter
 *
 * Inject User object from security context to request attributes
 * to use it for param conversion as a "user" parameter.
 * Acts like the {@link \Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\DoctrineParamConverter DoctrineParamConverter}
 */
class DoctrineParamConverter implements ParamConverterInterface
{
    /**
     * @var BaseDoctrineParamConverter
     */
    private $paramConverter;
    /**
     * @var SecurityContextInterface
     */
    private $securityContext;

    public function __construct(
        BaseDoctrineParamConverter $paramConverter,
        SecurityContextInterface $securityContext
    ) {
        $this->paramConverter = $paramConverter;
        $this->securityContext = $securityContext;
    }

    /**
     * @param Request $request
     * @param ParamConverter|ConfigurationInterface $configuration
     * @return bool
     */
    function apply(Request $request, ConfigurationInterface $configuration)
    {
        $needUser = isset($configuration->getOptions()['mapping']['loggedInUser']);
        $hasUser = $request->attributes->has('loggedInUser');
        $userAdded = false;
        if (!$hasUser && $needUser) {
            $token = $this->securityContext->getToken();
            if ($token) {
                $userAdded = true;
                $request->attributes->set('loggedInUser', $token->getUser());
            }
        }
        $result = $this->paramConverter->apply($request, $configuration);
        if ($userAdded) {
            $request->attributes->remove('loggedInUser');
        }

        return $result;
    }

    function supports(ConfigurationInterface $configuration)
    {
        return $this->paramConverter->supports($configuration);
    }
}