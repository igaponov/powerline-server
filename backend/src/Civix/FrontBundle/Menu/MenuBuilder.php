<?php

namespace Civix\FrontBundle\Menu;

use Symfony\Component\HttpFoundation\Request;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Civix\CoreBundle\Entity\Group;

/**
 * Class for build menu.
 */
class MenuBuilder
{
    protected $securityContext;
    protected $isLoggedIn;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @param FactoryInterface $factory
     * @param TokenStorageInterface $tokenStorage
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(
        FactoryInterface $factory,
        TokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
//        parent::__construct($factory);

        $this->securityContext = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        $this->isLoggedIn = $this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY');
    }

    /**
     * @return \Knp\Menu\ItemInterface
     */
    public function createMainMenu()
    {
        $menu = $this->createNavbarMenuItem();

        if ($this->securityContext->isGranted('ROLE_SUPERUSER')) {
            $menu->addChild('Manage approvals', array('route' => 'civix_front_superuser_approvals'));
            $menu->addChild(
                'Manage Users',
                array('route' => 'civix_front_superuser_manage_representatives')
            )
                ->setExtras(
                    array(
                        'routes' => array(
                            'civix_front_superuser_manage_representatives',
                            'civix_front_superuser_manage_groups',
                            'civix_front_superuser_manage_users',
                            'civix_front_superuser_manage_limits',
                        )
                    )
                );
            $menu->addChild(
                'Local Groups',
                array('route' => 'civix_front_superuser_local_groups')
            )
                ->setExtras(
                    array(
                        'routes' => array(
                            'civix_front_superuser_local_groups',
                            'civix_front_superuser_local_groups_assign',
                            'civix_front_superuser_local_groups_by_state',
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
            $menu->addChild('Question Report', ['route' => 'civix_front_superuser_report_index'])
                ->setExtras(['routes' => [
                    'civix_front_superuser_report_index',
                    'civix_front_superuser_report_question',
                ],
            ]);
            $menu->addChild('Settings', array('route' => 'civix_front_superuser_settings_states'))
                ->setExtras(array('routes' => array(
                    'civix_front_superuser_settings_states',
                    ))
                );
        } else {
            $menu->addChild('Superuser', array('route' => 'civix_front_superuser'))
                ->setExtras(array('routes' => array('civix_front_superuser', 'civix_front_superuser_login')));
        }

        return $menu;
    }

    /**
     * @param Request $request
     *
     * @return \Knp\Menu\ItemInterface
     */
    public function createQuestionMenu(Request $request)
    {
        $menu = $this->createSubnavbarMenuItem();
        $menu->setChildrenAttribute('class', 'nav nav-tabs');

        if ($this->securityContext->isGranted('ROLE_REPRESENTATIVE')) {
            $menu->addChild('New Question', array('route' => 'civix_front_representative_question_index'));
            $menu->addChild('Sending out response', array('route' => 'civix_front_representative_question_response'));
            $menu->addChild('Question Archive', array('route' => 'civix_front_representative_question_archive'));
        } elseif ($this->securityContext->isGranted('ROLE_GROUP')) {
            $menu->addChild('New Question', array('route' => 'civix_front_group_question_index'));
            $menu->addChild('Sending out response', array('route' => 'civix_front_group_question_response'));
            $menu->addChild('Question Archive', array('route' => 'civix_front_group_question_archive'));
        } elseif ($this->securityContext->isGranted('ROLE_SUPERUSER')) {
            $menu->addChild('New Question', array('route' => 'civix_front_superuser_question_index'));
            $menu->addChild('Sending out response', array('route' => 'civix_front_superuser_question_response'));
            $menu->addChild('Question Archive', array('route' => 'civix_front_superuser_question_archive'));
        }

        return $menu;
    }

    /**
     * @param Request $request
     *
     * @return \Knp\Menu\ItemInterface
     */
    public function createPetitionMenu(Request $request)
    {
        $menu = $this->createSubnavbarMenuItem();
        $menu->setChildrenAttribute('class', 'nav nav-pills pull-right');

        if ($this->securityContext->isGranted('ROLE_REPRESENTATIVE')) {
            $menu->addChild('Create New Petition', array('route' => 'civix_front_representative_petition_new'));
        } elseif ($this->securityContext->isGranted('ROLE_GROUP')) {
            $menu->addChild('Create New Petition', array('route' => 'civix_front_group_petition_new'));
        }

        return $menu;
    }

    /**
     * @param Request $request
     *
     * @return \Knp\Menu\ItemInterface
     */
    public function createManageMenu(Request $request)
    {
        $menu = $this->createSubnavbarMenuItem();
        $menu->setChildrenAttribute('class', 'nav nav-tabs');

        if ($this->securityContext->isGranted('ROLE_SUPERUSER')) {
            $menu->addChild('Manage Representatives', array('route' => 'civix_front_superuser_manage_representatives'));
            $menu->addChild('Manage Groups', array('route' => 'civix_front_superuser_manage_groups'));
            $menu->addChild('Manage Users', array('route' => 'civix_front_superuser_manage_users'));
            $menu->addChild('Manage Limits', array('route' => 'civix_front_superuser_manage_limits'));
        }

        return $menu;
    }

    /**
     * @param Request $request
     *
     * @return \Knp\Menu\ItemInterface
     */
    public function createMicroPetitionMenu(Request $request)
    {
        $menu = $this->createSubnavbarMenuItem();
        $menu->setChildrenAttribute('class', 'nav nav-tabs');

        if ($this->securityContext->isGranted('ROLE_GROUP')) {
            $menu->addChild('Archive', array('route' => 'civix_front_petitions'))
                ->setExtras(array('routes' => array('civix_front_petitions', 'civix_front_petitions_details')));
            $menu->addChild('Open', array('route' => 'civix_front_petitions_open'));
            $menu->addChild('Configuration', array('route' => 'civix_front_petitions_config'));
        }

        return $menu;
    }

    /**
     * @param Request $request
     *
     * @return \Knp\Menu\ItemInterface
     */
    public function createSettingsMenu(Request $request)
    {
        $menu = $this->createSubnavbarMenuItem();
        $menu->setChildrenAttribute('class', 'nav nav-tabs');

        if ($this->securityContext->isGranted('ROLE_GROUP')) {
            $menu->addChild('Membership Control', ['route' => 'civix_front_group_membership']);
            $menu->addChild('Required fields', ['route' => 'civix_front_group_fields']);
            $menu->addChild('Payment Information', ['route' => 'civix_front_group_paymentsettings_index']);
            $menu->addChild('Permissions', ['route' => 'civix_front_group_permissionsettings_index']);
        }

        return $menu;
    }

    /**
     * @param Request $request
     *
     * @return \Knp\Menu\ItemInterface
     */
    public function createGroupUserMenu()
    {
        $menu = $this->createSubnavbarMenuItem();
        $menu->setChildrenAttribute('class', 'nav nav-tabs');

        if ($this->securityContext->isGranted('ROLE_GROUP')) {
            if ($this->securityContext->getToken()->getUser()->getMembershipControl() ==
                Group::GROUP_MEMBERSHIP_APPROVAL
            ) {
                $menu->addChild('Manage approvals', array('route' => 'civix_front_group_manage_approvals'));
            }
            $menu->addChild('Group\'s members', array('route' => 'civix_front_group_members'));
            $menu->addChild('Invites', array('route' => 'civix_front_group_invite'));
            $menu->addChild('Sections', array('route' => 'civix_front_group_sections_index'))
                ->setExtras(array('routes' => array('civix_front_group_sections_index',
                        'civix_front_group_sections_new',
                        'civix_front_group_sections_edit',
                        'civix_front_group_sections_view',
                    )));
        }

        return $menu;
    }

    /**
     * @param Request $request
     *
     * @return \Knp\Menu\ItemInterface
     */
    public function createQuestionOptions(Request $request)
    {
        $menu = $this->createSubnavbarMenuItem();
        $menu->setChildrenAttribute('class', 'nav nav-pills pull-right');

        if ($this->securityContext->isGranted('ROLE_REPRESENTATIVE')) {
            $menu->addChild('Create New Question', ['route' => 'civix_front_representative_question_new']);
        } elseif ($this->securityContext->isGranted('ROLE_GROUP')) {
            $menu->addChild('Create Question', ['route' => 'civix_front_group_question_new']);
        } elseif ($this->securityContext->isGranted('ROLE_SUPERUSER')) {
            $menu->addChild('Create New Question', ['route' => 'civix_front_superuser_question_new']);
        }

        return $menu;
    }

    protected function createMainDropdownMenuItem(ItemInterface $rootItem, $title, $push_right = true, $icon = array(), $knp_item_options = array())
    {
        $rootItem
            ->setAttribute('class', 'nav navbar-nav')
        ;
        if ($push_right) {
            $this->pushRight($rootItem);
        }
        $dropdown = $rootItem->addChild($title, array_merge(array('uri' => '#'), $knp_item_options))
            ->setLinkattribute('class', 'dropdown-main-toggle')
            ->setLinkattribute('data-toggle', 'dropdown')
            ->setAttribute('class', 'dropdown-main')
            ->setChildrenAttribute('class', 'dropdown-main-menu')
        ;

        // TODO: make XSS safe $icon contents escaping
        switch (true) {
            case isset($icon['icon']) || isset($icon['glyphicon']):
                $this->addIcon($dropdown, $icon);
                break;
            case isset($icon['caret']) && $icon['caret'] === true:
                $this->addCaret($dropdown, $icon);
        }

        return $dropdown;
    }
}
