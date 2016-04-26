<?php

namespace Civix\ApiBundle\Form\Type\Poll;

use Civix\ApiBundle\EventListener\QuestionTypeSubscriber;
use Civix\CoreBundle\Entity\Poll\Question;
use Civix\CoreBundle\Entity\UserInterface;
use Civix\CoreBundle\Service\PollClassNameFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Question form type.
 */
class QuestionType extends AbstractType
{
    /**
     * @var UserInterface
     */
    private $user;

    public function __construct(UserInterface $user)
    {
        $this->user = $user;
    }

    /**
     * Set form fields.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // all
            ->add('subject', 'text', [
                'description' => 'Subject',
            ])
            ->add('report_recipient_group', 'text', [
                'property_path' => 'reportRecipientGroup',
                'description' => 'Representative',
                'required' => false,
            ])
            // event, payment request
            ->add('title', 'text', [
                'description' => 'Poll title (event, payment request)',
            ])
            ->add('is_allow_outsiders', 'checkbox', [
                'property_path' => 'isAllowOutsiders',
                'description' => 'Allow outsiders (event, payment request)',
                'required' => false,
            ])
            // event
            ->add('started_at', 'datetime', [
                'property_path' => 'startedAt',
                'description' => 'Start datetime (event)',
                'widget' => 'single_text',
            ])
            ->add('finished_at', 'datetime', [
                'property_path' => 'finishedAt',
                'description' => 'Finish datetime (event)',
                'widget' => 'single_text',
            ])
            // payment request
            ->add('is_crowdfunding', 'checkbox', [
                'property_path' => 'isCrowdfunding',
                'description' => 'Is crowdfunding (payment request)',
                'required' => false,
            ])
            ->add('crowdfunding_goal_amount', 'integer', [
                'property_path' => 'crowdfundingGoalAmount',
                'description' => 'Crowdfunding goal amount (payment request)',
                'required' => false,
            ])
            ->add('crowdfunding_deadline', 'datetime', [
                'property_path' => 'crowdfundingDeadline',
                'description' => 'Crowdfunding deadline (payment request)',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('is_crowdfunding_completed', 'checkbox', [
                'property_path' => 'isCrowdfundingCompleted',
                'description' => 'Is crowdfunding completed (payment request)',
                'required' => false,
            ])
            ->add('crowdfunding_pledged_amount', 'integer', [
                'property_path' => 'crowdfundingPledgedAmount',
                'description' => 'Crowdfunding pledge amount (payment request)',
                'required' => false,
            ])
            // petition
            ->add('is_outsiders_sign', 'checkbox', [
                'property_path' => 'isOutsidersSign',
                'description' => 'Is outsiders sign (petition)',
                'required' => false,
            ])
            ->add('petition_title', 'text', [
                'property_path' => 'petitionTitle',
                'description' => 'Petition tile (petition)',
            ])
            ->add('petition_body', 'text', [
                'property_path' => 'petitionBody',
                'description' => 'Petition body (petition)',
            ])
            ->addEventSubscriber(new QuestionTypeSubscriber($this->user));
    }

    /**
     * Get unique name for form.
     *
     * @return string
     */
    public function getName()
    {
        return '';
    }

    /**
     * Set default form option.
     *
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
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
        ]);
    }
}
