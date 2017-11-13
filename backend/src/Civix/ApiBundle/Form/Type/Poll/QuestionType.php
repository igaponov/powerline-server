<?php

namespace Civix\ApiBundle\Form\Type\Poll;

use Civix\ApiBundle\EventListener\QuestionTypeSubscriber;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\GroupSection;
use Civix\CoreBundle\Entity\LeaderContentRootInterface;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Repository\GroupSectionRepository;
use Civix\CoreBundle\Service\PollClassNameFactory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Question form type.
 */
class QuestionType extends AbstractType
{
    /**
     * @var LeaderContentRootInterface
     */
    private $user;

    /**
     * Set form fields.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->user = $options['root_model'];
        $builder
            // all
            ->add('subject', Type\TextType::class, [
                'description' => 'Subject',
            ])
            // event, payment request
            ->add('title', Type\TextType::class, [
                'description' => 'Poll title (event, payment request)',
            ])
            ->add('is_allow_outsiders', Type\CheckboxType::class, [
                'property_path' => 'isAllowOutsiders',
                'description' => 'Allow outsiders (event, payment request)',
                'required' => false,
            ])
            // event
            ->add('started_at', Type\DateTimeType::class, [
                'property_path' => 'startedAt',
                'description' => 'Start datetime (event)',
                'widget' => 'single_text',
            ])
            ->add('finished_at', Type\DateTimeType::class, [
                'property_path' => 'finishedAt',
                'description' => 'Finish datetime (event)',
                'widget' => 'single_text',
            ])
            // payment request
            ->add('is_crowdfunding', Type\CheckboxType::class, [
                'property_path' => 'isCrowdfunding',
                'description' => 'Is crowdfunding (payment request)',
                'required' => false,
            ])
            ->add('crowdfunding_goal_amount', Type\IntegerType::class, [
                'property_path' => 'crowdfundingGoalAmount',
                'description' => 'Crowdfunding goal amount (payment request)',
                'required' => false,
            ])
            ->add('crowdfunding_deadline', Type\DateTimeType::class, [
                'property_path' => 'crowdfundingDeadline',
                'description' => 'Crowdfunding deadline (payment request)',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('is_crowdfunding_completed', Type\CheckboxType::class, [
                'property_path' => 'isCrowdfundingCompleted',
                'description' => 'Is crowdfunding completed (payment request)',
                'required' => false,
            ])
            ->add('crowdfunding_pledged_amount', Type\IntegerType::class, [
                'property_path' => 'crowdfundingPledgedAmount',
                'description' => 'Crowdfunding pledge amount (payment request)',
                'required' => false,
            ])
            // petition
            ->add('is_outsiders_sign', Type\CheckboxType::class, [
                'property_path' => 'isOutsidersSign',
                'description' => 'Is outsiders sign (petition)',
                'required' => false,
            ])
            ->add('petition_title', Type\TextType::class, [
                'property_path' => 'petitionTitle',
                'description' => 'Petition tile (petition)',
            ])
            ->add('petition_body', Type\TextType::class, [
                'property_path' => 'petitionBody',
                'description' => 'Petition body (petition)',
            ])
            ->add('group_sections', EntityType::class, [
                'class' => GroupSection::class,
                'multiple' => true,
                'query_builder' => function (GroupSectionRepository $repository) {
                if ($this->user instanceof Group) {
                    return $repository->getFindByGroupQueryBuilder($this->user);
                } else {
                    return $repository->createQueryBuilder('s');
                }
                },
                'description' => 'Array of group section\'s ids',
            ])
            ->addEventSubscriber(new QuestionTypeSubscriber($this->user));
    }

    /**
     * Get unique name for form.
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return '';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['root_model'])
            ->setAllowedTypes('root_model', [LeaderContentRootInterface::class, 'null'])
            ->setDefaults([
                'data_class' => Question::class,
                'empty_data' => function(Form $form) {
                    $type = $form->get('type')->getData();
                    $entityClass = PollClassNameFactory::getEntityClass(
                        $type,
                        $this->user->getType()
                    );

                    return new $entityClass;
                },
                'csrf_protection' => false,
                'validation_groups' => function(Form $form) {
                    $groups = ['pre-validation'];
                    if (is_object($form->getData())) {
                        $groups = array_merge($groups, ['Default', 'api-poll']);
                    }

                    return $groups;
                },
                'allow_extra_fields' => true,
                'root_model' => null,
            ]);
    }
}
