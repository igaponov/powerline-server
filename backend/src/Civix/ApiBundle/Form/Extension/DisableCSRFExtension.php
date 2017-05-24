<?php

namespace Civix\ApiBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Disable csrf in form for API requests
 */
class DisableCSRFExtension extends AbstractTypeExtension
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request || strpos($request->getUri(), '/api/') === false) {
            return;
        }

        $resolver->setDefault('csrf_protection', false);
    }

    public function getExtendedType()
    {
        return FormType::class;
    }
}
