<?php

namespace Civix\FrontBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Class for build menu.
 */
class MenuBuilder
{
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;
    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @param FactoryInterface $factory
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(
        FactoryInterface $factory,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->factory = $factory;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @return \Knp\Menu\ItemInterface
     */
    public function createMainMenu()
    {
        $menu = $this->factory->createItem('root', array(
            'childrenAttributes' => [
                'class' => 'nav nav-sidebar',
                ],
            'push_right' => true,
        ));

        if ($this->authorizationChecker->isGranted('ROLE_SUPERUSER')) {
            $menu->addChild('Manage approvals', array('route' => 'civix_front_representative_approvals'))
                ->setExtra('routes', [
                    'civix_front_representative_approvals',
                    'civix_front_representative_edit',
                ]);
            $menu->addChild(
                'Manage Users',
                array('route' => 'civix_front_representative_index')
            )
                ->setExtras(
                    array(
                        'routes' => array(
                            'civix_front_representative_index',
                            'civix_front_groups',
                            'civix_front_user_index',
                            'civix_front_limits_index',
                        )
                    )
                );
            $menu->addChild(
                'Local Groups',
                array('route' => 'civix_front_local_groups')
            )
                ->setExtras(
                    array(
                        'routes' => array(
                            'civix_front_local_groups',
                            'civix_front_local_group',
                            'civix_front_local_groups_by_state',
                        )
                    )
                );
            $menu->addChild('Blog', array('route' => 'civix_front_superuser_post_index'))
                ->setExtras(
                    array(
                        'routes' => array(
                            'civix_front_superuser_post_index',
                            'civix_front_superuser_post_new',
                            'civix_front_superuser_post_edit',
                        ),
                    )
                );
            $menu->addChild('Posts', array('route' => 'civix_front_post_index'))
                ->setExtras(
                    array(
                        'routes' => array(
                            'civix_front_post_index',
                        ),
                    )
                );
            $menu->addChild('Petitions', array('route' => 'civix_front_petition_index'))
                ->setExtras(
                    array(
                        'routes' => array(
                            'civix_front_petition_index',
                        ),
                    )
                );
            $menu->addChild('Question Report', ['route' => 'civix_front_superuser_report_index'])
                ->setExtras(['routes' => [
                    'civix_front_superuser_report_index',
                    'civix_front_superuser_report_question',
                ],
            ]);
            $menu->addChild('Representatives', array('route' => 'civix_front_representative_bulk'))
                ->setExtras(array('routes' => array(
                        'civix_front_representative_bulk',
                    ))
                );
            $menu->addChild('Settings', array('route' => 'civix_front_superuser_settings_states'))
                ->setExtras(array('routes' => array(
                    'civix_front_superuser_settings_states',
                    ))
                );
        }

        return $menu;
    }

    /**
     * @return \Knp\Menu\ItemInterface
     */
    public function createManageMenu()
    {
        $menu = $this->factory->createItem('root', array(
            'navbar' => true,
            'push_right' => true,
        ));
        $menu->setChildrenAttribute('class', 'nav nav-tabs');

        if ($this->authorizationChecker->isGranted('ROLE_SUPERUSER')) {
            $menu->addChild('Manage Representatives', array('route' => 'civix_front_representative_index'));
            $menu->addChild('Manage Groups', array('route' => 'civix_front_groups'));
            $menu->addChild('Manage Users', array('route' => 'civix_front_user_index'));
            $menu->addChild('Manage Limits', array('route' => 'civix_front_limits_index'));
        }

        return $menu;
    }
}
