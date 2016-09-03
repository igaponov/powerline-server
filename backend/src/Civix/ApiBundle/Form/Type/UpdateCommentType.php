<?php
namespace Civix\ApiBundle\Form\Type;

use Civix\ApiBundle\Form\KeyToValueTransformer;
use Civix\CoreBundle\Entity\BaseComment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class UpdateCommentType extends AbstractType
{
    /**
     * @var string
     */
    private $entityClass;

    public function __construct($entityClass)
    {
        $this->entityClass = $entityClass;
    }

    public function getName()
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choices = BaseComment::getPrivacyLabels();
        $builder->add('privacy', 'text', [
           'description' => 'Privacy, one of: '.implode(', ', $choices),
        ]);
        $builder->get('privacy')->addModelTransformer(new KeyToValueTransformer($choices));
   }

    public function getParent()
    {
        return new CommentType($this->entityClass);
    }
}