<?php

namespace Civix\ApiBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType as BaseEmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class EmailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $email = $event->getData();
            if (strpos($email, '@') === false) {
                return;
            }
            $email = strtolower($email);
            list($local, $domain) = explode('@', $email);
            $local = strstr($local, '+', true) ?: $local;
            $local = str_replace('.', '', $local);
            $event->setData($local.'@'.$domain);
        });
    }

    public function getParent()
    {
        return BaseEmailType::class;
    }

    public function getBlockPrefix()
    {
        return 'email';
    }
}