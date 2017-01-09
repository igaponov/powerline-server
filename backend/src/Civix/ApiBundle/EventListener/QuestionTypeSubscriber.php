<?php
namespace Civix\ApiBundle\EventListener;

use Civix\CoreBundle\Entity\LeaderContentRootInterface;
use Civix\CoreBundle\Service\PollClassNameFactory;
use Doctrine\Common\Inflector\Inflector;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class QuestionTypeSubscriber implements EventSubscriberInterface
{
    /**
     * @var LeaderContentRootInterface
     */
    private $user;

    public function __construct(LeaderContentRootInterface $user = null)
    {
        $this->user = $user;
    }
    
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'preRemoveExtraFields',
            FormEvents::POST_SET_DATA => 'addTypeField',
            FormEvents::PRE_SUBMIT => 'postRemoveExtraFields',
        ];
    }

    /**
     * When form is used for update, remove extra fields on pre-set-data
     * to avoid getting attributes to fill form fields
     *
     * @param FormEvent $event
     */
    public function preRemoveExtraFields(FormEvent $event) {
        $data = $event->getData();
        if (is_object($data)) {
            $this->removeExtraFields($event->getForm(), $data);
        }
    }

    /**
     * If form data is empty (on create) add "type" field
     * to generate entity by type
     *
     * @param FormEvent $event
     */
    public function addTypeField(FormEvent $event) {
        $data = $event->getData();
        $form = $event->getForm();

        if (!$data) {
            $form->add('type', TextType::class, [
                'description' => 'Poll type (group (regular poll), representative, event, news, payment_request, petition)',
                'mapped' => false,
                'constraints' => [
                    new NotBlank([
                        'groups' => ['pre-validation'],
                    ]),
                ],
            ]);
        }
    }

    /**
     * When form is used for create, remove extra fields on pre-submit
     * to avoid setting attributes that doesn't exist in an entity
     *
     * @param FormEvent $event
     */
    public function postRemoveExtraFields(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
        $formData = $form->getData();
        if ($formData === null && is_array($data) && isset($data['type'])) {
            $formData = $entityClass = PollClassNameFactory::getEntityClass(
                $data['type'],
                $this->user->getType()
            );
        } elseif (!is_object($formData)) {
            foreach ($form as $name => $child) {
                if ($name !== 'type') {
                    $form->remove($name);
                }
            }

            return;
        }
        $this->removeExtraFields($form, $formData);
    }

    private function removeExtraFields(FormInterface $form, $data)
    {
        foreach ($form as $name => $child) {
            $setter = 'set'.Inflector::classify($name);
            if ($name !== 'type' && !method_exists($data, $setter)) {
                $form->remove($name);
            }
        }
    }
}