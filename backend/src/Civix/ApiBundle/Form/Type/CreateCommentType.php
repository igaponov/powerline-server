<?php
namespace Civix\ApiBundle\Form\Type;

use Civix\CoreBundle\Entity\BaseComment;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;

class CreateCommentType extends AbstractType
{
    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var string
     */
    private $entityClass;

    public function __construct(EntityManager $em, $entityClass)
    {
        $this->em = $em;
        $this->entityClass = $entityClass;
    }

    public function getName()
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('parent_comment', 'integer', [
                'property_path' => 'parentComment',
            ]);

        $builder->get('parent_comment')->addModelTransformer(new CallbackTransformer(
            function ($value) {
                if ($value instanceof BaseComment) {
                    return $value->getId();
                }

                return $value;
            }, function ($value) {
                if ($value) {
                    return $this->em->getRepository($this->entityClass)->find($value);
                }

                return $value;
            }
        ));
    }

    public function getParent()
    {
        return new CommentType($this->entityClass);
    }
}