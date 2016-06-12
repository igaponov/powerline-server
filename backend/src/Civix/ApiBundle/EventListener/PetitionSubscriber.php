<?php
namespace Civix\ApiBundle\EventListener;

use Civix\CoreBundle\Event\Micropetition\AnswerEvent;
use Civix\CoreBundle\Event\Micropetition\PetitionEvent;
use Civix\CoreBundle\Event\MicropetitionEvents;
use Civix\CoreBundle\Service\Micropetitions\PetitionManager;
use Civix\CoreBundle\Service\Poll\CommentManager;
use Civix\CoreBundle\Service\Settings;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PetitionSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var Settings
     */
    private $settings;
    /**
     * @var PetitionManager
     */
    private $petitionManager;
    /**
     * @var CommentManager
     */
    private $commentManager;

    public static function getSubscribedEvents()
    {
        return [
            MicropetitionEvents::PETITION_PRE_CREATE => 'setExpire',
            MicropetitionEvents::PETITION_CREATE => [
                ['addHashTags'],
                ['addMicropetitionRootComment'],
            ],
            MicropetitionEvents::PETITION_UPDATE => 'addHashTags',
        ];
    }

    public function __construct(
        EntityManager $em, 
        Settings $settings,
        PetitionManager $petitionManager,
        CommentManager $commentManager
    ) {
        $this->em = $em;
        $this->settings = $settings;
        $this->petitionManager = $petitionManager;
        $this->commentManager = $commentManager;
    }

    public function setExpire(PetitionEvent $event)
    {
        $petition = $event->getPetition();
        $key = 'micropetition_expire_interval_'.$petition->getGroup()->getGroupType();
        $interval = $this->settings->get($key)->getValue();
        $petition->setExpireAt(new \DateTime("+$interval days"));
        $petition->setUserExpireInterval($interval);
    }

    public function addHashTags(PetitionEvent $event)
    {
        $this->em->getRepository('CivixCoreBundle:HashTag')
            ->addForPetition($event->getPetition());
    }

    public function addMicropetitionRootComment(PetitionEvent $event)
    {
        $this->commentManager->addMicropetitionRootComment($event->getPetition());
    }
}